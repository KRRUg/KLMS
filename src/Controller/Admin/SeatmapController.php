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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * @IsGranted("ROLE_ADMIN_SEATMAP")
 * @Route("/seatmap", name="seatmap")
 */
class SeatmapController extends AbstractController
{
    private IdmRepository $userRepo;
    private EntityManagerInterface $em;
    private GamerService $gamerService;
    private SeatmapService $seatmapService;
    private SeatRepository $seatRepository;

    public function __construct(EntityManagerInterface $em, GamerService $gamerService, IdmManager $manager, SeatmapService $seatmapService, SeatRepository $seatRepository)
    {
        $this->em = $em;
        $this->userRepo = $manager->getRepository(User::class);
        $this->gamerService = $gamerService;
        $this->seatmapService = $seatmapService;
        $this->seatRepository = $seatRepository;
    }

    /**
     * @Route("", name="", methods={"GET"})
     */
    public function index()
    {
        $seats = $this->seatmapService->getSeatmap();
        return $this->render('admin/seatmap/index.html.twig', [
            'seatmap' => $seats,
            'users' => $this->seatmapService->getSeatedUser($seats),
        ]);
    }

    /**
     * @Route("/show/{id}", name="_seat_edit", methods={"GET", "POST"})
     * @ParamConverter()
     */
    public function seatShow(Seat $seat, Request $request)
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

    /**
     * @Route("/seatposition", name="_seat_pos", methods={"POST"})
     */
    public function changeSeatPosition(Request $request)
    {
        $json = json_decode($request->getContent());
        $seat = $this->seatRepository->findOneBy(['id' => $json->id]);

        if ($seat) {
            $seat->setPosX($json->left);
            $seat->setPosY($json->top);
            $this->em->flush();

            return new JsonResponse(json_encode(true), 200, [], true);
        }
        return new JsonResponse(json_encode(false), 200, [], true);

    }

    /**
     * @Route("/seat/delete/{id}", name="_seat_delete", methods={"GET"})
     * @ParamConverter()
     */
    public function deleteSeat(Seat $seat)
    {
            $this->em->remove($seat);
            $this->em->flush();

        $this->addFlash('success', "Sitzplatz {$seat->getSector()}-{$seat->getSeatNumber()} erfolgreich gelöscht.");
        return $this->redirectToRoute('admin_seatmap');
    }

    /**
     * @Route("/seats/create", name="_seat_create", methods={"GET","POST"})
     */
    public function createSeats(Request $request)
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
                //Create a single Seat
                $this->em->persist($seat);
                $this->em->flush();
                $this->addFlash('success', "Sitzplatz {$seat->getSector()}-{$seat->getSeatNumber()} erfolgreich erstellt.");

                return $this->redirectToRoute('admin_seatmap');
            } else {
                //Create multiple Seats
                $x = $seat->getPosX();
                $y = $seat->getPosY();
                $i = 1;
                $seatNumber = $seat->getSeatNumber();
                while ($i <= $count) {
                    $newSeat = clone($seat);
                    $newSeat->setPosX($x);
                    $newSeat->setPosY($y);
                    $newSeat->setSeatNumber($seatNumber);
                    //Create the Seat
                    $this->em->persist($newSeat);

                    if ($seat->getChairPosition() == "top" || $seat->getChairPosition() == "bottom") {
                        $x += 22;
                    } else {
                        $y += 22;
                    }
                    $seatNumber += 2;
                    $i++;
                }
                $this->em->flush();
                $this->addFlash('success', $count . " Sitzplätze erfolgreich erstellt.");

                return $this->redirectToRoute('admin_seatmap');
            }
        }

        return $this->render('admin/seatmap/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
