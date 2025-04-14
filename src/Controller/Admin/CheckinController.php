<?php

namespace App\Controller\Admin;

use App\Entity\Ticket;
use App\Entity\User;
use App\Idm\Exception\PersistException;
use App\Idm\IdmManager;
use App\Service\KlcsConnectorService;
use App\Exception\TicketLivecycleException;
use App\Form\UserSelectType;
use App\Form\UserType;
use App\Service\TicketService;
use App\Service\UserService;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Uuid as UuidConstraint;

#[IsGranted('ROLE_ADMIN_CHECKIN')]
#[Route(path: '/checkin', name: 'checkin')]
class CheckinController extends AbstractController
{
    private readonly TicketService $ticketService;
    private readonly UserService $userService;
    private KlcsConnectorService $klcsConnectorService;
    private IdmManager $manager;

    public function __construct(KlcsConnectorService $klcsConnectorService,
                                IdmManager $manager,
                                TicketService $ticketService,
                                UserService $userService
    ){
        $this->klcsConnectorService = $klcsConnectorService;
        $this->manager = $manager;
        $this->ticketService = $ticketService;
        $this->userService = $userService;
    }

    private function createUserSelectForm(): FormInterface
    {
        $form = $this->createFormBuilder();
        $form->add('user', UserSelectType::class);

        return $form->getForm();
    }

    private function createKlcsAccountBindingForm(User $user): FormInterface
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_checkin_klcs_create', ['uuid' => $user->getUuid()]));
        $form->add('klcsUuid', TextType::class, [
            'required' => true,
            'label' => 'KLCS Account UUID (QR Code)',
            'constraints' => [
                new UuidConstraint(),
                new NotBlank(),
            ]
            ]);

        return $form->getForm();
    }

    private function createTicketCheckinForm(Ticket $ticket): FormInterface
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_checkin_update', ['id' => $ticket->getId()]));
                $form->add('punch', SubmitType::class);

        return $form->getForm();
    }

    private function createUserVerifyForm(User $user): FormInterface
    {

        return $this->createForm(UserType::class, $user, [
            'disable_on_lock' => false,
            'with_image' => false,
            'action' => $this->generateUrl('admin_checkin_user_verify', ['uuid' => $user->getUuid()]),
        ]);
    }

    #[Route(path: '', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $tickets = $this->ticketService->queryTickets();
        $uuids = array_map(fn (Ticket $t) => $t->getRedeemer(), $tickets);
        $uuids = array_filter($uuids, fn (?UuidInterface $uuid) => !empty($uuid));
        $users = $this->userService->getUsers($uuids, assoc: true);

        return $this->render('admin/checkin/index.html.twig', [
            'tickets' => $tickets,
            'users' => $users,
        ]);
    }
    private static function clickedIfExists(FormInterface $form, string $field): bool
    {
        return $form->has($field) ? $form->get($field)->isClicked() : false;
    }

    #[Route(path: '/{id}', name: '_update', methods: ['POST'])]
    public function update(Request $request, Ticket $ticket): Response
    {
        $form = $this->createTicketCheckinForm($ticket);
        $form->handleRequest($request);
        $user = $this->userService->getUsers([$ticket->getRedeemer()])[0];
        $error = "";
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                switch (true) {
                    case self::clickedIfExists($form, 'punch'):
                        $this->ticketService->punchTicket($ticket);
                        break;
                    default:
                        $this->addFlash('error', "Aktion konnte nicht durchgeführt werden");
                        return $this->redirectToRoute('admin_checkin');
                }
            } catch (TicketLivecycleException $exception) {
                $this->addFlash('error', "Aktion konnte nicht durchgeführt werden ({$exception->getMessage()}).");
                return $this->redirectToRoute('admin_checkin');
            }
            if (!empty($error)) {
                $this->addFlash('error', $error);
            } else {
                $this->addFlash('success', "User " . $user->getNickname() . " erfolgreich eingechecked!");
            }
        }

        return $this->redirectToRoute('admin_checkin');
    }

    #[Route(path: '/{id}', name: '_show', methods: ['GET'])]
    public function show(Ticket $ticket): Response
    {
        $ticketCheckinForm = $this->createTicketCheckinForm($ticket);
        $user = $this->ticketService->userByTicket($ticket);
        $userVerifyForm = $this->createUserVerifyForm($user);
        $klcsAccountBindingForm = $this->createKlcsAccountBindingForm($user);

        return $this->render('admin/checkin/show.html.twig', [
            'user' => $user,
            'ticket' => $ticket,
            'klcsEnabled' => $this->klcsConnectorService->isConnectorEnabled(),
            'ticketCheckinForm' => $ticketCheckinForm->createView(),
            'userVerifyForm' => $userVerifyForm->createView(),
            'klcsAccountBindingForm' => $klcsAccountBindingForm->createView()
        ]);
    }

    #[Route(path: '/klcs/create/{uuid}', name: '_klcs_create', methods: ['POST'])]
    public function createKlcsAccountBinding(Request $request, string $uuid): Response
    {

        if(!(Uuid::isValid(Uuid::fromString($uuid))) || empty($uuid)) {
            throw new BadRequestHttpException('Not a valid UUID');
        }
        $user = $this->userService->getUsers([Uuid::fromString($uuid)])[0];

        if (empty($user)) {
            throw $this->createNotFoundException('User not found');
        }

        $form = $this->createKlcsAccountBindingForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->klcsConnectorService->createAccount($user, Uuid::fromString($form->get('klcsUuid')->getData()));

                if ($request->isXmlHttpRequest()) {
                    return new Response(null, Response::HTTP_NO_CONTENT);
                }

                // FIXME: Doesn't work on AJAX Requests
                $this->addFlash('success', 'KLCS Account verbunden!');

                return $this->redirectToRoute('admin_checkin');
            } catch (\Exception $e) {
                $form->get('klcsUuid')->addError(new FormError('Fehler beim Verbinden des KLCS Accounts: ' . $e->getMessage()));
            }
        }

        return $this->render('admin/checkin/_form.klcs_binding.html.twig', [
            'form' => $form->createView(),
        ], new Response(
            null,
            $form->isSubmitted() && !$form->isValid() ? 422 : 200,
        ));
    }

    #[Route(path: '/user/verify/{uuid}', name: '_user_verify', methods: ['POST'])]
    public function verifyUser(Request $request, string $uuid): Response
    {

        if(!(Uuid::isValid(Uuid::fromString($uuid))) || empty($uuid)) {
            throw new BadRequestHttpException('Not a valid UUID');
        }
        $user = $this->userService->getUsers([Uuid::fromString($uuid)])[0];

        if (empty($user)) {
            throw $this->createNotFoundException('User not found');
        }

        $form = $this->createUserVerifyForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $form->getData();
                $user->setPersonalDataConfirmed(true);
                $this->manager->persist($user);
                $this->manager->flush();

                if ($request->isXmlHttpRequest()) {
                    return new Response(null, Response::HTTP_NO_CONTENT);
                }

                $this->addFlash('success', 'User erfolgreich bearbeitet!');
                return $this->redirectToRoute('admin_checkin');
            } catch (PersistException $e) {
                match ($e->getCode()) {
                    PersistException::REASON_NON_UNIQUE => $form->get('nickname')->addError(new FormError('Nickname und/oder Email ist schon in Verwendung')),
                    default => $form->get('nickname')->addError(new FormError('Es ist ein unerwarteter Fehler beim User bearbeiten aufgetreten')),
                };
            }

        }

        return $this->render('admin/checkin/_form.user_verify.html.twig', [
            'form' => $form->createView(),
        ], new Response(
            null,
            $form->isSubmitted() && !$form->isValid() ? 422 : 200,
        ));
    }


}
