<?php

namespace App\Controller\Admin;

use App\Entity\Seat;
use App\Entity\SeatOrientation;
use App\Form\ClanSelectType;
use App\Form\SeatType;
use App\Form\UserSelectType;
use App\Repository\SeatRepository;
use App\Service\SeatmapService;
use App\Service\SettingService;
use App\Service\TicketService;
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
    private readonly EntityManagerInterface $em;
    private readonly SeatmapService $seatmapService;
    private readonly TicketService $ticketService;
    private readonly SettingService $settingService;
    private readonly SeatRepository $seatRepository;
    private readonly SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $em,
                                SeatmapService         $seatmapService,
                                SettingService         $settingService,
                                TicketService          $ticketService,
                                SeatRepository         $seatRepository,
                                SerializerInterface    $serializer)
    {
        $this->em = $em;
        $this->seatmapService = $seatmapService;
        $this->settingService = $settingService;
        $this->seatRepository = $seatRepository;
        $this->serializer = $serializer;
        $this->ticketService = $ticketService;
    }

    #[Route(path: '', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $seats = $this->seatmapService->getSeatmap();
        $dim = $this->seatmapService->getDimension();

        return $this->render('admin/seatmap/index.html.twig', [
            'seatmap' => $seats,
            'dim' => $dim,
            'users' => $this->seatmapService->getSeatedUser($seats),
            'clans' => $this->seatmapService->getReservedClans($seats),
        ]);
    }

    #[Route(path: '/show/{id}', name: '_seat_edit', methods: ['GET', 'POST'])]
    public function seatShow(Seat $seat, Request $request): Response
    {
        $seatUser = $this->seatmapService->getSeatOwner($seat);

        $form = $this->createForm(SeatType::class, $seat, [
            'action' => $this->generateUrl('admin_seatmap_seat_edit', ['id' => $seat->getId()]),
        ]);

        $form->add('owner', UserSelectType::class, ['required' => false, 'hydrate' => false]);
        $form->add('clanReservation', ClanSelectType::class, ['required' => false, 'hydrate' => false]);
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

    #[Route(path: '/seatposition', name: '_seat_pos', methods: ['POST'], format: 'json')]
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
            } else {
                // Create multiple Seats
                $seatSize = $this->settingService->get('lan.seatmap.styles.seat_size');
                $seatMultiplier = $this->settingService->get('lan.seatmap.styles.seat_tablewidth_multiplier');
                $seatSpacing = $this->settingService->get('lan.seatmap.styles.seat_multiple_seats_distance');
                
                $x = $seat->getPosX();
                $y = $seat->getPosY();
                $seatNumber = $seat->getSeatNumber();
                for ($i = 0; $i <= $count; $i++) {
                    $newSeat = clone $seat;
                    $newSeat->setPosX($x);
                    $newSeat->setPosY($y);
                    $newSeat->setSeatNumber($seatNumber);
                    // Create the Seat
                    $this->em->persist($newSeat);

                    if ($seat->getChairPosition() == SeatOrientation::NORTH || $seat->getChairPosition() == SeatOrientation::SOUTH) {
                        $x += $seatSize * $seatMultiplier + $seatSpacing;
                    } else {
                        $y += $seatSize * $seatMultiplier + $seatSpacing;
                    }
                    $seatNumber += 2;
                }
                $this->em->flush();
                $this->addFlash('success', $count.' Sitzplätze erfolgreich erstellt.');
            }
            return $this->redirectToRoute('admin_seatmap');
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
                'isPaid' => !empty($seatUser) && $this->ticketService->isUserRegistered($seatUser),
            ];
        }

        $response = new Response($this->serializer->serialize($csvData, 'csv'));
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="seatmap.csv"');

        return $response;
    }
}
