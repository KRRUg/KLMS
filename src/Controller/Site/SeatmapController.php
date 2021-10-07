<?php

namespace App\Controller\Site;


use App\Entity\Seat;
use App\Entity\User;
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
    private EntityManagerInterface $em;
    private GamerService $gamerService;
    private SeatmapService $seatmapService;
    private SettingService $settingService;
    private LoggerInterface $logger;
    private IdmRepository $userRepo;

    public function __construct(
        EntityManagerInterface $entityManager,
        GamerService           $gamerService,
        LoggerInterface        $logger,
        SettingService         $settingService,
        SeatmapService         $seatmapService,
        IdmManager             $manager
    )
    {
        $this->em = $entityManager;
        $this->gamerService = $gamerService;
        $this->userRepo = $manager->getRepository(User::class);
        $this->logger = $logger;
        $this->settingService = $settingService;
        $this->seatmapService = $seatmapService;
    }

    /**
     * @Route("", name="")
     */
    public function index()
    {


        return $this->render('site/seatmap/index.html.twig', [
            'seatmap' => $this->seatmapService->getSeatmap(),
        ]);
    }

    /**
     * @Route("/seat/{id}", name="_seat_show", methods={"GET", "POST"})
     * @ParamConverter()
     */
    public function seatShow(Seat $seat, Request $request)
    {
        $formView = null;
        $gamerEligible = false;
        $seatmapLocked = false;

        if ($this->settingService->isSet('lan.seatmap.locked')) {
            $seatmapLocked = $this->settingService->get('lan.seatmap.locked');
        }

        if ($seat->getType() == 'seat') {
            if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
                $user = $this->getUser()->getUser();
                $gamerEligible = $this->seatmapService->hasSeatEligibility($user);

                if ($this->seatmapService->isSeatOwner($seat, $user)) {

                    $fb = $this->createFormBuilder()
                        ->add('action', HiddenType::class, [
                            'data' => 'unbook'
                        ]);

                    $fb->setAction($this->generateUrl('seatmap_seat_show', ['id' => $seat->getId()]));

                    $form = $fb->getForm();
                    $form->handleRequest($request);

                    if ($form->isSubmitted() && $form->isValid()) {
                        $action = $form->get('action')->getData();

                        if ($action == 'unbook') {
                            if ($seatmapLocked) {
                                $this->addFlash('error', "Der Sitzplan ist aktuell von den Administratoren gesperrt!");

                                return $this->redirectToRoute('seatmap');
                            }
                            $this->seatmapService->unBookSeat($seat, $user);
                            $this->addFlash('success', "Sitzplatz {$seat->getSector()}-{$seat->getSeatNumber()} erfolgreich freigegeben.");

                            return $this->redirectToRoute('seatmap');
                        }
                        throw new GamerLifecycleException($user, 'User already owns Seat!');
                    }
                    $formView = $form->createView();
                } else {
                    if ($this->seatmapService->canBookSeat($seat, $user)) {
                        $fb = $this->createFormBuilder()
                            ->add('action', HiddenType::class, [
                                'data' => 'book'
                            ]);

                        $fb->setAction($this->generateUrl('seatmap_seat_show', ['id' => $seat->getId()]));

                        $form = $fb->getForm();
                        $form->handleRequest($request);

                        if ($form->isSubmitted() && $form->isValid()) {
                            $action = $form->get('action')->getData();

                            if ($action == 'book') {
                                if ($seatmapLocked) {
                                    $this->addFlash('error', "Der Sitzplan ist aktuell von den Administratoren gesperrt!");

                                    return $this->redirectToRoute('seatmap');
                                }
                                $this->seatmapService->bookSeat($seat, $user);
                                $this->addFlash('success', "Sitzplatz {$seat->getSector()}-{$seat->getSeatNumber()} erfolgreich reserviert!");

                                return $this->redirectToRoute('seatmap');
                            }
                            throw new GamerLifecycleException($user, "Cannot unbook seat that isn't yours!");
                        }
                        $formView = $form->createView();
                    }
                }
            }
        }


        if ($seat->getOwner()) {
            $seatUser = $this->gamerService->getUserFromGamer($seat->getOwner());
        } else {
            $seatUser = null;
        }


        return $this->render('site/seatmap/seat.html.twig', [
            'seat' => $seat,
            'user' => $seatUser,
            'form' => $formView,
            'gamerEligible' => $gamerEligible,
        ]);
    }
}
