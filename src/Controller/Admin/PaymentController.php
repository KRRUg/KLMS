<?php

namespace App\Controller\Admin;

use App\Entity\Ticket;
use App\Entity\User;
use App\Exception\TicketLivecycleException;
use App\Form\UserSelectType;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\TicketService;
use App\Service\UserService;
use Ramsey\Uuid\UuidInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_ADMIN_PAYMENT')]
#[Route(path: '/payment', name: 'payment')]
class PaymentController extends AbstractController
{
    private const CSRF_TOKEN_PAYMENT = 'paymentToken';

    private readonly TicketService $ticketService;
    private readonly UserService $userService;
    private readonly IdmRepository $userRepo;

    public function __construct(TicketService $ticketService,
                                UserService $userService,
                                IdmManager $manager)
    {
        $this->ticketService = $ticketService;
        $this->userService = $userService;
        $this->userRepo = $manager->getRepository(User::class);
    }

    private function createUserSelectForm(): FormInterface
    {
        $form = $this->createFormBuilder();
        $form->add('user', UserSelectType::class);

        return $form->getForm();
    }

    #[Route(path: '', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $tickets = $this->ticketService->queryTickets();
        $uuids = array_map(fn (Ticket $t) => $t->getRedeemer(), $tickets);
        $uuids = array_filter($uuids, fn (?UuidInterface $uuid) => !empty($uuid));
        $users = $this->userService->getUsers($uuids, assoc: true);

        return $this->render('admin/payment/index.html.twig', [
            'tickets' => $tickets,
            'users' => $users,
            'form_add' => $this->createUserSelectForm()->createView(),
        ]);
    }

    // TODO add create new Ticket Controllerd

    #[Route(path: '', name: '_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $form = $this->createUserSelectForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData()['user'];
            if (empty($user)) {
                $this->addFlash('error', 'Ungültigen User ausgewählt.');
            } elseif ($this->ticketService->isUserRegistered($user)) {
                $this->addFlash('warning', "User {$user->getNickname()} ist schon registriert.");
            } else {
                try {
                    $ticket = $this->ticketService->registerUser($user);
                    $this->addFlash('success', "User {$user->getNickname()} wurde zur Veranstaltung mit Ticket {$ticket->getCode()} registriert.");
                } catch (TicketLivecycleException) {
                    $this->addFlash('error', "User {$user->getNickname()}  konnte nicht registriert werden.");
                }
            }
        }

        return $this->redirectToRoute('admin_payment');
    }

    #[Route(path: '/{uuid}', name: '_update', methods: ['POST'])]
    public function update(Request $request, string $uuid): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_PAYMENT, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token presented');
        }

        $user = $this->userRepo->findOneById($uuid);
        if (empty($user)) {
            throw $this->createNotFoundException('User not found');
        }

        $action = $request->request->get('action');
        try {
            switch ($action) {
                // TODO add actions for Ticket Service
                default:
                    $this->addFlash('error', 'Invalid action specified.');

                    return $this->redirectToRoute('admin_payment');
            }
        } catch (TicketLivecycleException $exception) {
            $this->addFlash('error', "Aktion konnte nicht durchgeführt werden ({$exception->getMessage()}).");

            return $this->redirectToRoute('admin_payment');
        }
        $this->addFlash('success', "Änderung an User {$user->getNickname()} erfolgreich.");

        return $this->redirectToRoute('admin_payment');
    }

    #[Route(path: '/{uuid}', name: '_show', methods: ['GET'])]
    public function show(string $uuid): Response
    {
        $user = $this->userRepo->findOneById($uuid);

        if (empty($user)) {
            throw $this->createNotFoundException('User not found');
        }

        $ticket = $this->ticketService->getTicketUser($user);

        // TODO change twig to ticket
        return $this->render('admin/payment/show.html.twig', [
            'user' => $user,
            'ticket' => $ticket,
            'csrf_token' => self::CSRF_TOKEN_PAYMENT,
        ]);
    }
}
