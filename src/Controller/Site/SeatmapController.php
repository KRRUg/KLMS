<?php

namespace App\Controller\Site;

use App\Entity\Seat;
use App\Exception\GamerLifecycleException;
use App\Service\SeatmapService;
use App\Service\SettingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/seatmap', name: 'seatmap')]
class SeatmapController extends AbstractController
{
    private readonly SeatmapService $seatmapService;
    private readonly SettingService $settingService;

    public function __construct(
        SettingService $settingService,
        SeatmapService $seatmapService)
    {
        $this->settingService = $settingService;
        $this->seatmapService = $seatmapService;
    }

    #[Route(path: '', name: '')]
    public function index(): Response
    {
        if (!$this->settingService->get('lan.seatmap.enabled', false)) {
            if ($this->settingService->get('lan.signup.enabled', false)) {
                $this->addFlash('warning', 'Sitzplan ist noch nicht verfügbar');

                return $this->redirectToRoute('index');
            } else {
                throw $this->createNotFoundException();
            }
        }

        $seats = $this->seatmapService->getSeatmap();

        return $this->render('site/seatmap/index.html.twig', [
            'seatmap' => $seats,
            'users' => $this->seatmapService->getSeatedUser($seats),
        ]);
    }

    private function generateForm(Seat $seat, string $action): FormInterface
    {
        $fb = $this->createFormBuilder()
            ->add('action', HiddenType::class, [
                'data' => $action,
            ]);

        $fb->setAction($this->generateUrl('seatmap_seat_show', ['id' => $seat->getId()]));

        return $fb->getForm();
    }

    #[Route(path: '/seat/{id}', name: '_seat_show', methods: ['GET', 'POST'])]
    public function seatShow(Seat $seat, Request $request): Response
    {
        $view = null;
        $locked = $this->settingService->get('lan.seatmap.locked') === true;
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $user = $this->getUser()->getUser();
            if ($this->seatmapService->canBookSeat($seat, $user)) {
                $form = $this->generateForm($seat, 'book');
            } elseif ($this->seatmapService->isSeatOwner($seat, $user)) {
                $form = $this->generateForm($seat, 'unbook');
            } else {
                $form = null;
            }
            if ($form) {
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    if ($locked) {
                        $this->addFlash('error', 'Der Sitzplan ist aktuell von den Administratoren gesperrt!');
                    } else {
                        $action = $form->get('action')->getData();
                        try {
                            switch ($action) {
                                case 'book':
                                    $this->seatmapService->bookSeat($seat, $user);
                                    $this->addFlash('success', "Sitzplatz {$seat->generateSeatName()} erfolgreich reserviert!");
                                    break;
                                case 'unbook':
                                    $this->seatmapService->unBookSeat($seat, $user);
                                    $this->addFlash('success', "Sitzplatz {$seat->generateSeatName()} erfolgreich freigegeben.");
                                    break;
                            }
                        } catch (GamerLifecycleException) {
                            $this->addFlash('error', 'Aktion konnte nicht durchgeführt werden!');
                        }
                    }

                    return $this->redirectToRoute('seatmap');
                }
                $view = $form->createView();
            }
        }

        $owner = $this->seatmapService->getSeatOwner($seat);

        return $this->render('site/seatmap/seat.html.twig', [
            'seat' => $seat,
            'user' => $owner,
            'form' => $view,
        ]);
    }
}
