<?php

namespace App\Controller\Admin;

use App\Entity\Seat;
use App\Entity\User;
use App\Entity\UserGamer;
use App\Form\SeatType;
use App\Form\UserSelectType;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\SeatRepository;
use App\Service\GamerService;
use App\Service\SeatmapService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Positive;

#[IsGranted('ROLE_ADMIN_SEATMAP')]
#[Route(path: '/seatmap', name: 'seatmap')]
class SeatmapController extends AbstractController
{
    private readonly IdmRepository $userRepo;
    private readonly EntityManagerInterface $em;
    private readonly GamerService $gamerService;
    private readonly SeatmapService $seatmapService;
    private readonly SeatRepository $seatRepository;
    private readonly SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $em, GamerService $gamerService, IdmManager $manager, SeatmapService $seatmapService, SeatRepository $seatRepository, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->userRepo = $manager->getRepository(User::class);
        $this->gamerService = $gamerService;
        $this->seatmapService = $seatmapService;
        $this->seatRepository = $seatRepository;
        $this->serializer = $serializer;
    }

    #[Route(path: '', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $seats = $this->seatmapService->getSeatmap();

        return $this->render('admin/seatmap/index.html.twig', [
            'seatmap' => $seats,
            'users' => $this->seatmapService->getSeatedUser($seats),
        ]);
    }

    #[Route(path: '/show/{id}', name: '_seat_edit', methods: ['GET', 'POST'])]
    public function seatShow(Seat $seat, Request $request): Response
    {
        if ($seat->getOwner()) {
            $seatUser = $this->gamerService->getUserFromGamer($seat->getOwner());
        } else {
            $seatUser = null;
        }

        $form = $this->createForm(SeatType::class, $seat, [
            'action' => $this->generateUrl('admin_seatmap_seat_edit', ['id' => $seat->getId()]),
        ]);

        $form->add('owner', UserSelectType::class, ['type' => UserGamer::class, 'required' => false]);
        $form->setData($seat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $seat = $form->getData();
            $this->em->flush();
            $this->addFlash('success', "Sitzplatz {$seat->getSector()}-{$seat->getSeatNumber()} erfolgreich geändert.");

            return $this->redirectToRoute('admin_seatmap');
        }

        return $this->render('admin/seatmap/seat.html.twig', [
            'form' => $form->createView(),
            'user' => $seatUser,
        ]);
    }

    #[Route(path: '/seatposition', name: '_seat_pos', methods: ['POST'])]
    public function changeSeatPosition(Request $request): Response
    {
        $json = json_decode($request->getContent(), null, 512, JSON_THROW_ON_ERROR);
        $seat = $this->seatRepository->findOneBy(['id' => $json->id]);

        if ($seat) {
            $seat->setPosX($json->left);
            $seat->setPosY($json->top);
            $this->em->flush();

            return new JsonResponse(json_encode(true, JSON_THROW_ON_ERROR), Response::HTTP_OK, [], true);
        }

        return new JsonResponse(json_encode(false, JSON_THROW_ON_ERROR), Response::HTTP_OK, [], true);
    }

    #[Route(path: '/seat/delete/{id}', name: '_seat_delete', methods: ['GET'])]
    public function deleteSeat(Seat $seat): Response
    {
        $this->em->remove($seat);
        $this->em->flush();

        $this->addFlash('success', "Sitzplatz {$seat->getSector()}-{$seat->getSeatNumber()} erfolgreich gelöscht.");

        return $this->redirectToRoute('admin_seatmap');
    }

    #[Route(path: '/seats/create', name: '_seat_create', methods: ['GET', 'POST'])]
    public function createSeats(Request $request): Response
    {
        $offsetX = intval($request->query->get('x'));
        $offsetY = intval($request->query->get('y'));

        $seat = new Seat();
        $seat->setPosX($offsetX);
        $seat->setPosY($offsetY);

        $form = $this->createForm(SeatType::class, $seat, [
            'action' => $this->generateUrl('admin_seatmap_seat_create'),
        ]);
        $form->add('count', IntegerType::class, [
            'label' => 'Anzahl',
            'mapped' => false,
            'data' => 1,
            'constraints' => [new Positive()],
        ]);
        $form->remove('owner');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $count = $form->get('count')->getData();
            $seat = $form->getData();

            if ($count === 1) {
                // Create a single Seat
                $this->em->persist($seat);
                $this->em->flush();
                $this->addFlash('success', "Sitzplatz {$seat->getSector()}-{$seat->getSeatNumber()} erfolgreich erstellt.");

                return $this->redirectToRoute('admin_seatmap');
            } else {
                // Create multiple Seats
                $x = $seat->getPosX();
                $y = $seat->getPosY();
                $i = 1;
                $seatNumber = $seat->getSeatNumber();
                while ($i <= $count) {
                    $newSeat = clone $seat;
                    $newSeat->setPosX($x);
                    $newSeat->setPosY($y);
                    $newSeat->setSeatNumber($seatNumber);
                    // Create the Seat
                    $this->em->persist($newSeat);

                    if ($seat->getChairPosition() == 'top' || $seat->getChairPosition() == 'bottom') {
                        $x += 29;
                    } else {
                        $y += 29;
                    }
                    $seatNumber += 2;
                    ++$i;
                }
                $this->em->flush();
                $this->addFlash('success', $count.' Sitzplätze erfolgreich erstellt.');

                return $this->redirectToRoute('admin_seatmap');
            }
        }

        return $this->render('admin/seatmap/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/export', name: '_export', methods: ['GET'])]
    public function export(): Response
    {
        $csvData = [];

        $seatmap = $this->seatRepository->findTakenSeats();
        $seatmapUsers = $this->seatmapService->getSeatedUser($seatmap);

        foreach ($seatmap as $seat) {
            $seatName = $seat->getName() ?: null;
            $seatUser = $seatmapUsers[$seat->getId()];
            $clanTags = [];

            foreach ($seatUser->getClans() as $clan) {
                $clanTags[] = $clan->getClantag();
            }

            $csvData[] = [
                'id' => $seatUser->getId(),
                'nickname' => $seatUser->getNickname(),
                'vorname' => $seatUser->getFirstname(),
                'nachname' => $seatUser->getSurname(),
                'seat' => $seat->getLocation(),
                'clans' => implode(', ', $clanTags),
                'seatname' => $seatName,
            ];
        }

        $response = new Response($this->serializer->serialize($csvData, 'csv'));
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="seatmap.csv"');

        return $response;
    }
}
