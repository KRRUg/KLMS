<?php

namespace App\Controller\Admin;

use App\Entity\Ticket;
use App\Exception\TicketLivecycleException;
use App\Form\UserSelectType;
use App\Service\TicketService;
use App\Service\TicketState;
use App\Service\UserService;
use Ramsey\Uuid\UuidInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_ADMIN_PAYMENT')]
#[Route(path: '/payment', name: 'payment')]
class PaymentController extends AbstractController
{
    private readonly TicketService $ticketService;
    private readonly UserService $userService;

    public function __construct(TicketService $ticketService,
                                UserService   $userService)
    {
        $this->ticketService = $ticketService;
        $this->userService = $userService;
    }

    private function createUserSelectForm(): FormInterface
    {
        $form = $this->createFormBuilder();
        $form->add('user', UserSelectType::class);

        return $form->getForm();
    }

    private function createTicketModificationForm(Ticket $ticket): FormInterface
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_payment_update', ['id' => $ticket->getId()]));
        $can_delete_ticket = empty($ticket->getShopOrderPosition());
        switch ($ticket->getState()) {
            case TicketState::NEW:
                $form->add('user', UserSelectType::class);
                $form->add('assign', SubmitType::class);
                if ($can_delete_ticket) $form->add('delete', SubmitType::class);
                break;
            case TicketState::REDEEMED:
                $form->add('unassign', SubmitType::class);
                $form->add('punch', SubmitType::class);
                if ($can_delete_ticket) $form->add('delete', SubmitType::class);
                break;
            case TicketState::PUNCHED:
                $form->add('unpunch', SubmitType::class);
                $form->add('unassign', SubmitType::class);
                if ($can_delete_ticket) $form->add('delete', SubmitType::class);
                break;
        }
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

    private static function clickedIfExists(FormInterface $form, string $field): bool
    {
        return $form->has($field) ? $form->get($field)->isClicked() : false;
    }

    #[Route(path: '/{id}', name: '_update', methods: ['POST'])]
    public function update(Request $request, Ticket $ticket): Response
    {
        $form = $this->createTicketModificationForm($ticket);
        $form->handleRequest($request);
        $id = $ticket->getId();
        $error = "";
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                switch (true) {
                    case self::clickedIfExists($form, 'assign'):
                        $user = $form->get('user')->getData();
                        if ($this->ticketService->isUserRegistered($user)) {
                            $error = "User {$user->getNickname()} ist schon registriert.";
                        } else {
                            $this->ticketService->redeemTicket($ticket, $user);
                        }
                        break;
                    case self::clickedIfExists($form, 'unassign'):
                        $this->ticketService->unassignTicket($ticket);
                        break;
                    case self::clickedIfExists($form, 'punch'):
                        $this->ticketService->punchTicket($ticket);
                        break;
                    case self::clickedIfExists($form, 'unpunch'):
                        $this->ticketService->unpunchTicket($ticket);
                        break;
                    case self::clickedIfExists($form, 'delete'):
                        $this->ticketService->deleteTicket($ticket);
                        break;
                    default:
                        $this->addFlash('error', "Aktion konnte nicht durchgeführt werden");
                        return $this->redirectToRoute('admin_payment');
                }
            } catch (TicketLivecycleException $exception) {
                $this->addFlash('error', "Aktion konnte nicht durchgeführt werden ({$exception->getMessage()}).");
                return $this->redirectToRoute('admin_payment');
            }
            if (!empty($error)) {
                $this->addFlash('error', $error);
            } else {
                $this->addFlash('success', "Änderung an Ticket #{$id} erfolgreich.");
            }
        }

        return $this->redirectToRoute('admin_payment');
    }

    #[Route(path: '/{id}', name: '_show', methods: ['GET'])]
    public function show(Request $request, Ticket $ticket): Response
    {
        $form = $this->createTicketModificationForm($ticket);
        $user = $this->ticketService->userByTicket($ticket);

        return $this->render('admin/payment/show.html.twig', [
            'user' => $user,
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }
}
