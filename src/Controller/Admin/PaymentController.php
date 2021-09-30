<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\User;
use App\Exception\GamerLifecycleException;
use App\Form\PermissionType;
use App\Form\UserSelectType;
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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN_PAYMENT")
 * @Route("/payment", name="payment")
 */
class PaymentController extends AbstractController
{
    private const CSRF_TOKEN_PAYMENT = "paymentToken";

    private GamerService $gamerService;
    private IdmRepository $userRepo;

    public function __construct(GamerService $gamerService,
                                IdmManager $manager)
    {
        $this->gamerService = $gamerService;
        $this->userRepo = $manager->getRepository(User::class);
    }

    private function createUserSelectForm(): FormInterface
    {
        $form = $this->createFormBuilder();
        $form->add('user', UserSelectType::class);
        return $form->getForm();
    }

    /**
     * @Route("", name="", methods={"GET"})
     */
    public function index(Request $request)
    {
        $gamers = $this->gamerService->getGamers();
        return $this->render('admin/payment/index.html.twig', [
            'gamers' => $gamers,
            'form_add' => $this->createUserSelectForm()->createView(),
        ]);
    }

    /**
     * @Route("", name="_add", methods={"POST"})
     */
    public function add(Request $request)
    {
        $form = $this->createUserSelectForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData()['user'];
            if (empty($user)) {
                $this->addFlash('error', "Ungültigen User ausgewählt.");
            } elseif ($this->gamerService->gamerHasRegistered($user)) {
                $this->addFlash('warning', "User {$user->getNickname()} ist schon registriert.");
            } else {
                try {
                    $this->gamerService->gamerRegister($user);
                    $this->addFlash('success', "User {$user->getNickname()} wurde zur Veranstaltung registriert.");
                } catch (GamerLifecycleException $exception) {
                    $this->addFlash('error', "User {$user->getNickname()}  konnte nicht registriert werden.");
                }
            }
        }
        return $this->redirectToRoute('admin_payment');
    }

    /**
     * @Route("/{uuid}", name="_update", methods={"POST"})
     */
    public function update(Request $request, string $uuid)
    {
        $token = $request->request->get('_token');
        if(!$this->isCsrfTokenValid(self::CSRF_TOKEN_PAYMENT, $token)) {
            throw $this->createAccessDeniedException("Invalid CSRF token presented");
        }

        $user = $this->userRepo->findOneById($uuid);
        if (empty($user)) {
            throw $this->createNotFoundException('User not found');
        }

        $action = $request->request->get('action');
        try{
            switch ($action) {
                case "register":
                    $this->gamerService->gamerRegister($user);
                    break;
                case "unregister":
                    $this->gamerService->gamerUnregister($user);
                    break;
                case "pay":
                    $this->gamerService->gamerPay($user);
                    break;
                case "unpay":
                    $this->gamerService->gamerUnPay($user);
                    break;
                case "checkin":
                case "checkout":
                    // TODO implement me
                    $this->addFlash('error', 'Not yet implemented action.');
                    break;
                default:
                    $this->addFlash('error', 'Invalid action specified.');
                    return $this->redirectToRoute('admin_payment');
            }
        } catch(GamerLifecycleException $exception) {
            $this->addFlash('error', "Aktion konnte nicht durchgeführt werden ({$exception->getMessage()}).");
            return $this->redirectToRoute('admin_payment');
        }
        $this->addFlash('success', "Änderung an User {$user->getNickname()} erfolgreich.");
        return $this->redirectToRoute('admin_payment');
    }

    /**
     * @Route("/{uuid}", name="_show", methods={"GET"})
     */
    public function show(Request $request, string $uuid)
    {
        $user = $this->userRepo->findOneById($uuid);

        if (empty($user)) {
            throw $this->createNotFoundException('User not found');
        }

        $gamer = $this->gamerService->gamerGetStatus($user);

        return $this->render('admin/payment/show.html.twig', [
            'user' => $user,
            'gamer' => $gamer,
            'csrf_token' => self::CSRF_TOKEN_PAYMENT,
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
