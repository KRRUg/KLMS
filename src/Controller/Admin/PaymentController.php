<?php

namespace App\Controller\Admin;

use App\Entity\ShopOrderPositionAddon;
use App\Entity\Ticket;
use App\Entity\User;
use App\Exception\TicketLifecycleException;
use App\Form\UserSelectType;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\TicketRepository;
use App\Service\SeatmapService;
use App\Service\TicketService;
use App\Service\TicketState;
use App\Service\UserService;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;

#[IsGranted('ROLE_ADMIN_PAYMENT')]
#[Route(path: '/payment', name: 'payment')]
class PaymentController extends AbstractController
{
    private const FORM_FREE_TICKET = 'ticket_free';
    private const FORM_REGISTER_TICKET = 'ticket_register';
    private const FORM_MODIFY_TICKET = 'ticket_modify';
    private const REDIRECT_ROUTES = ['admin_payment', 'admin_payment_quick_checkin', 'admin_payment_desktop_checkin'];

    private readonly TicketService $ticketService;
    private readonly UserService $userService;
    private readonly SeatmapService $seatmapService;
    private readonly IdmRepository $userRepo;
    private readonly TicketRepository $ticketRepository;
    private readonly FormFactoryInterface $formFactory;

    public function __construct(TicketService $ticketService,
                                UserService   $userService,
                                SeatmapService $seatmapService,
                                IdmManager $idmManager,
                                TicketRepository $ticketRepository,
                                FormFactoryInterface $formFactory)
    {
        $this->ticketService = $ticketService;
        $this->userService = $userService;
        $this->seatmapService = $seatmapService;
        $this->userRepo = $idmManager->getRepository(User::class);
        $this->ticketRepository = $ticketRepository;
        $this->formFactory = $formFactory;
    }

    private function createFreeTicketForm(): FormInterface
    {
        return $this->formFactory->createNamedBuilder(self::FORM_FREE_TICKET, FormType::class)
            ->setAction($this->generateUrl('admin_payment_add'))
            ->setMethod('POST')
            ->add('comment', TextareaType::class, [
                'required' => true,
                'label' => 'Kommentar (Pflichtfeld)',
                'attr' => [
                    'rows' => 3,
                    'maxlength' => 1000,
                    'placeholder' => 'Warum ist dieses Ticket kostenlos?',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 1000),
                ],
            ])
            ->getForm();
    }

    private function createRegisterTicketForm(): FormInterface
    {
        return $this->formFactory->createNamedBuilder(self::FORM_REGISTER_TICKET, FormType::class)
            ->setAction($this->generateUrl('admin_payment_add'))
            ->setMethod('POST')
            ->add('user', UserSelectType::class, [
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('comment', TextareaType::class, [
                'required' => true,
                'label' => 'Kommentar (Pflichtfeld)',
                'attr' => [
                    'rows' => 3,
                    'maxlength' => 1000,
                    'placeholder' => 'Warum wird dieser Gamer manuell angemeldet?',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 1000),
                ],
            ])
            ->getForm();
    }

    private function createTicketModificationForm(Ticket $ticket, string $redirectRoute = 'admin_payment'): FormInterface
    {
        $form = $this->formFactory->createNamedBuilder(self::FORM_MODIFY_TICKET, FormType::class)
            ->setAction($this->generateUrl('admin_payment_update', [
                'id' => $ticket->getId(),
                'redirect' => $redirectRoute,
            ]));
        $can_delete_ticket = empty($ticket->getShopOrderPosition());
        switch ($ticket->getState()) {
            case TicketState::NEW:
                $form->add('user', UserSelectType::class, ['required' => false]);
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

    private static function clickedIfExists(FormInterface $form, string $field): bool
    {
        return $form->has($field) ? $form->get($field)->isClicked() : false;
    }

    private function redirectAfterTicketAction(string $redirectRoute, Ticket $ticket): Response
    {
        return $this->redirectToRoute(
            $redirectRoute,
            $redirectRoute === 'admin_payment_desktop_checkin' ? ['q' => $ticket->getCode()] : []
        );
    }

    #[Route(path: '/quick-checkin', name: '_quick_checkin', methods: ['GET'])]
    public function quickCheckin(): Response
    {
        return $this->render('admin/payment/checkin.html.twig');
    }

    #[Route(path: '/desktop-checkin', name: '_desktop_checkin', methods: ['GET'])]
    public function desktopCheckin(Request $request): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $ticket = $query !== '' ? $this->ticketService->getTicketCode($query) : null;

        $searchResults = [];
        if (!$ticket && ctype_digit($query)) {
            $orderTickets = $this->ticketRepository->findByOrderId((int) $query);
            if (count($orderTickets) === 1) {
                $ticket = $orderTickets[0];
            } else {
                foreach ($orderTickets as $orderTicket) {
                    $searchResults[] = [
                        'ticket' => $orderTicket,
                        'user' => $this->ticketService->userByTicket($orderTicket),
                    ];
                }
            }
        }

        if (!$ticket && $query !== '' && $searchResults === []) {
            $users = $this->userRepo->findFuzzy($query)->getPage(1, 10);
            foreach ($users as $user) {
                $userTicket = $this->ticketService->getTicketUser($user);
                if ($userTicket) {
                    $searchResults[] = ['ticket' => $userTicket, 'user' => $user];
                }
            }
        }

        $user = $ticket ? $this->ticketService->userByTicket($ticket) : null;
        $seats = $user ? $this->seatmapService->getUserSeats($user) : [];
        $seatNames = array_map(fn ($seat) => $seat->generateSeatName(), $seats);
        $addons = [];
        $order = $ticket?->getShopOrderPosition()?->getOrder();
        if ($order) {
            foreach ($order->getShopOrderPositions() as $position) {
                if ($position instanceof ShopOrderPositionAddon) {
                    $addons[] = $position;
                }
            }
        }

        return $this->render('admin/payment/desktop_checkin.html.twig', [
            'ticket' => $ticket,
            'user' => $user,
            'seats' => $seats,
            'seatNames' => $seatNames,
            'addons' => $addons,
            'form' => $ticket ? $this->createTicketModificationForm($ticket, 'admin_payment_desktop_checkin')->createView() : null,
            'searchQuery' => $query,
            'searchResults' => $searchResults,
        ]);
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
            'form_register' => $this->createRegisterTicketForm()->createView(),
            'form_free' => $this->createFreeTicketForm()->createView(),
        ]);
    }

    #[Route(path: '', name: '_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        if ($request->request->has(self::FORM_REGISTER_TICKET)) {
            $registerForm = $this->createRegisterTicketForm();
            $registerForm->handleRequest($request);
            if (!$registerForm->isSubmitted() || !$registerForm->isValid()) {
                $this->addFlash('error', 'Kommentar und User sind verpflichtend.');
                return $this->redirectToRoute('admin_payment');
            }

            $comment = trim((string) ($registerForm->getData()['comment'] ?? ''));
            $user = $registerForm->getData()['user'];
            if (empty($user)) {
                $this->addFlash('error', 'Ungültigen User ausgewählt.');
            } elseif ($this->ticketService->isUserRegistered($user)) {
                $this->addFlash('warning', "User {$user->getNickname()} ist schon registriert.");
            } else {
                try {
                    $ticket = $this->ticketService->registerUser($user, $comment);
                    $this->addFlash('success', "User {$user->getNickname()} wurde zur Veranstaltung mit Ticket {$ticket->getCode()} registriert.");
                } catch (TicketLifecycleException) {
                    $this->addFlash('error', "User {$user->getNickname()} konnte nicht registriert werden.");
                }
            }
            return $this->redirectToRoute('admin_payment');
        }

        if ($request->request->has(self::FORM_FREE_TICKET)) {
            $freeForm = $this->createFreeTicketForm();
            $freeForm->handleRequest($request);
            if (!$freeForm->isSubmitted() || !$freeForm->isValid()) {
                $this->addFlash('error', 'Kommentar ist verpflichtend.');
                return $this->redirectToRoute('admin_payment');
            }

            $comment = trim((string) ($freeForm->getData()['comment'] ?? ''));
            try {
                $ticket = $this->ticketService->createTicket($comment);
                $this->addFlash('success', "Ticket {$ticket->getCode()} wurde angelegt.");
            } catch (TicketLifecycleException) {
                $this->addFlash('error', "Ticket konnte nicht angelegt werden.");
            }
            return $this->redirectToRoute('admin_payment');
        }

        $this->addFlash('error', 'Ungültige Anfrage.');
        return $this->redirectToRoute('admin_payment');
    }

    #[Route(path: '/{id}', name: '_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(Request $request, Ticket $ticket): Response
    {
        $redirectRoute = (string) $request->query->get('redirect', 'admin_payment');
        if (!in_array($redirectRoute, self::REDIRECT_ROUTES, true)) {
            $redirectRoute = 'admin_payment';
        }

        $form = $this->createTicketModificationForm($ticket, $redirectRoute);
        $form->handleRequest($request);
        $id = $ticket->getId();
        $error = null;
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                switch (true) {
                    case self::clickedIfExists($form, 'assign'):
                        $user = $form->get('user')->getData();
                        if (empty($user)) {
                            $error = "Keinen User ausgewählt.";
                        } elseif ($this->ticketService->isUserRegistered($user)) {
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
                        $this->addFlash('error', 'Aktion konnte nicht durchgeführt werden.');
                        return $this->redirectToRoute($redirectRoute);
                }
            } catch (TicketLifecycleException) {
                $this->addFlash('error', 'Aktion konnte nicht durchgeführt werden.');
                return $this->redirectAfterTicketAction($redirectRoute, $ticket);
            }
            if ($error !== null) {
                $this->addFlash('error', $error);
            } else {
                $this->addFlash('success', "Änderung an Ticket #{$id} erfolgreich.");
            }
        }

        return $this->redirectAfterTicketAction($redirectRoute, $ticket);
    }

    #[Route(path: '/{id}', name: '_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Ticket $ticket): Response
    {
        $form = $this->createTicketModificationForm($ticket, 'admin_payment');
        $user = $this->ticketService->userByTicket($ticket);

        return $this->render('admin/payment/show.html.twig', [
            'user' => $user,
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/code/{code}', name: '_show_by_code', methods: ['GET'])]
    public function showFromCode(string $code): Response
    {
        $ticket = $this->ticketService->getTicketCode($code);
        if (is_null($ticket)) {
            $this->addFlash('error', sprintf('Kein Ticket mit dem Code "%s" gefunden.', $code));
            return $this->redirectToRoute('admin_payment_quick_checkin');
        }

        $form = $this->createTicketModificationForm($ticket, 'admin_payment_quick_checkin');
        $user = $this->ticketService->userByTicket($ticket);

        return $this->render('admin/payment/show.html.twig', [
            'user' => $user,
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

}
