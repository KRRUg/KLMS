<?php

namespace App\Controller\Site;


use App\Entity\Seat;
use App\Entity\User;
use App\Exception\GamerLifecycleException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\GamerService;
use App\Service\SeatmapService;
use App\Service\SettingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/seatmap", name="seatmap")
 */
class SeatmapController extends AbstractController
{
    private SeatmapService $seatmapService;
    private SettingService $settingService;

    public function __construct(
        SettingService         $settingService,
        SeatmapService         $seatmapService)
    {
        $this->settingService = $settingService;
        $this->seatmapService = $seatmapService;
    }

    /**
     * @Route("", name="")
     */
    public function index()
    {
        if (!$this->settingService->getOrDefault('lan.seatmap.enabled', false)) {
            if ($this->settingService->getOrDefault('lan.signup.enabled', false)) {
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

    private function generateForm(Seat $seat, string $action) {
        $fb = $this->createFormBuilder()
            ->add('action', HiddenType::class, [
                'data' => $action
            ]);

        $fb->setAction($this->generateUrl('seatmap_seat_show', ['id' => $seat->getId()]));
        return $fb->getForm();
    }

    /**
     * @Route("/seat/{id}", name="_seat_show", methods={"GET", "POST"})
     * @ParamConverter()
     */
    public function seatShow(Seat $seat, Request $request)
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
                        $this->addFlash('error', "Der Sitzplan ist aktuell von den Administratoren gesperrt!");
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
                        } catch (GamerLifecycleException $exception) {
                            $this->addFlash('error', "Aktion konnte nicht durchgeführt werden!");
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
