<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Exception\GamerLifecycleException;
use App\Form\UserSelectType;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\GamerService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
    }
}
