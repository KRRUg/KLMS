<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\User;
use App\Exception\GamerLifecycleException;
use App\Helper\EmailRecipient;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\UserGamerRepository;
use App\Service\EmailService;
use App\Service\GamerService;
use App\Service\SettingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN_PAYMENT")
 * @Route("/payment", name="payment")
 */
class GamerController extends BaseController
{
    private GamerService $gamerService;
    private UserGamerRepository $userGamerRepository;
    private IdmRepository $userRepo;
    private EmailService $emailService;
    private SettingService $settingsService;

    public function __construct(EmailService $emailService,
                                GamerService $gamerService,
                                IdmManager $manager,
                                UserGamerRepository $userGamerRepository,
                                SettingService $settingService)
    {
        $this->emailService = $emailService;
        $this->userRepo = $manager->getRepository(User::class);
        $this->gamerService = $gamerService;
        $this->userGamerRepository = $userGamerRepository;
        $this->settingsService = $settingService;
    }

    /**
     * @Route(".{_format}", name="", defaults={"_format"="html"}, methods={"GET"})
     */
    public function index(Request $request)
    {
        $gamers = $this->gamerService->getGamers();
        if ($request->getRequestFormat() === 'json') {
            return $this->apiResponse(
                array_values($gamers),
                true
            );
        } else {
            return $this->render('admin/payment/index.html.twig', [
                'gamers' => $gamers,
            ]);
        }
    }

    /**
     * @Route("/{uuid}", name="_show", methods={"GET", "POST"})
     */
    public function show(string $uuid, Request $request)
    {
        $user = $this->userRepo->findOneById($uuid);

        if (empty($user)) {
            throw $this->createNotFoundException('User not found');
        }

        $gamer = $this->userGamerRepository->findByUser($user);

        return $this->render('admin/payment/modal.html.twig', [
            'user' => $user,
            'gamer' => $gamer,
        ]);

//        if($gamer->hasPayed()) {
//            //unPay
//            $fb = $this->createFormBuilder()
//                ->add('action', HiddenType::class, [
//                    'data' => 'unpay'
//                ]);
//
//            $fb->setAction($this->generateUrl('admin_payment_show', ['uuid' => $user->getUuid()]));
//
//
//            $form = $fb->getForm();
//            $form->handleRequest($request);
//
//            if ($form->isSubmitted() && $form->isValid()) {
//                $action = $form->get('action')->getData();
//
//                if($action == 'unpay') {
//                    $this->gamerService->gamerUnPay($user);
//                    $this->addFlash('success', $user->getNickname() . ' erfolgreich als nicht-bezahlt markiert!');
//                    return $this->redirectToRoute('admin_payment');
//                }
//                throw new GamerLifecycleException($user, 'User didn\'t pay yet!');
//            }
//
//            return $this->render('admin/payment/modal.html.twig', [
//                'user' => $user,
//                'form' =>  $form->createView(),
//            ]);
//        } else {
//            //Pay
//            $fb = $this->createFormBuilder()
//                ->add('action', HiddenType::class, [
//                    'data' => 'pay'
//                ]);
//
//            $fb->setAction($this->generateUrl('admin_payment_show', ['uuid' => $user->getUuid()]));
//
//            $form = $fb->getForm();
//            $form->handleRequest($request);
//
//            if ($form->isSubmitted() && $form->isValid()) {
//                $action = $form->get('action')->getData();
//
//                if($action == 'pay') {
//                    $this->gamerService->gamerPay($user);
//                    $this->addFlash('success', $user->getNickname() . ' erfolgreich als bezahlt markiert!');
//                    if($this->settingsService->isSet('site.title')) {
//                        $message = "Wir haben dein Geld erhalten! Der Sitzplan für die \"{$this->settingsService->get('site.title')}\" wurde freigeschalten.";
//                    } else {
//                        $message = "Wir haben dein Geld erhalten! Der Sitzplan wurde freigeschalten.";
//                    }
//                    $this->emailService->scheduleHook(
//                        EmailService::APP_HOOK_CHANGE_NOTIFICATION,
//                        EmailRecipient::fromUser($this->getUser()->getUser()), [
//                            'message' => $message,
//                        ]
//                    );
//                    return $this->redirectToRoute('admin_payment');
//                }
//                throw new GamerLifecycleException($user, 'User paid already!');
//            }
//
//            return $this->render('admin/payment/pay.html.twig', [
//                'user' => $user,
//                'form' =>  $form->createView(),
//            ]);
//        }
    }
}
