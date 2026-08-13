<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Entity\BookingResource;
use App\Entity\User;
use App\Form\BookingResourceType;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/booking', name: 'booking')]
#[IsGranted('ROLE_ADMIN_CONTENT')]
class BookingController extends AbstractController
{
    private const CSRF_TOKEN_DELETE = 'bookingDeleteToken';
    private const CSRF_TOKEN_TOGGLE = 'bookingToggleToken';
    private const CSRF_TOKEN_CANCEL = 'bookingCancelToken';

    private readonly BookingService $bookingService;
    private readonly IdmRepository $userRepo;

    public function __construct(BookingService $bookingService, IdmManager $idmManager)
    {
        $this->bookingService = $bookingService;
        $this->userRepo = $idmManager->getRepository(User::class);
    }

    #[Route(path: '/', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $resources = $this->bookingService->getAllResources();

        return $this->render('admin/booking/index.html.twig', [
            'resources' => $resources,
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
            'csrf_token_toggle' => self::CSRF_TOKEN_TOGGLE,
        ]);
    }

    #[Route(path: '/new', name: '_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $resource = $this->bookingService->createResource();
        $form = $this->createForm(BookingResourceType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->bookingService->saveResource($resource);
                $this->addFlash('success', 'Ressource wurde erfolgreich erstellt.');

                return $this->redirectToRoute('admin_booking');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Fehler beim Erstellen der Ressource: ' . $e->getMessage());
            }
        }

        return $this->render('admin/booking/edit.html.twig', [
            'resource' => $resource,
            'form' => $form->createView(),
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
        ]);
    }

    #[Route(path: '/{id}/edit', name: '_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, BookingResource $resource): Response
    {
        $form = $this->createForm(BookingResourceType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->bookingService->saveResource($resource);
                $this->addFlash('success', 'Ressource wurde erfolgreich aktualisiert.');

                return $this->redirectToRoute('admin_booking');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Fehler beim Aktualisieren der Ressource: ' . $e->getMessage());
            }
        }

        return $this->render('admin/booking/edit.html.twig', [
            'resource' => $resource,
            'form' => $form->createView(),
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
        ]);
    }

    #[Route(path: '/{id}/toggle', name: '_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggle(Request $request, BookingResource $resource): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_TOGGLE, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        try {
            $this->bookingService->toggleResourceActive($resource);
            $status = $resource->isActive() ? 'aktiviert' : 'deaktiviert';
            $this->addFlash('success', "Ressource wurde $status.");
        } catch (\Exception $e) {
            $this->addFlash('error', 'Fehler beim Ändern des Status: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_booking');
    }

    #[Route(path: '/{id}/delete', name: '_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, BookingResource $resource): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_DELETE, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        try {
            $this->bookingService->deleteResource($resource);
            $this->addFlash('success', 'Ressource wurde erfolgreich gelöscht.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Fehler beim Löschen der Ressource: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_booking');
    }

    #[Route(path: '/{id}/reservations', name: '_reservations', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function reservations(BookingResource $resource): Response
    {
        $bookings = $this->bookingService->getBookingsForResource($resource);

        $uuids = array_map(static fn(Booking $b) => $b->getUserUuid(), $bookings);
        $this->userRepo->findById($uuids);

        $rows = array_map(fn(Booking $b) => [
            'booking' => $b,
            'user' => $this->userRepo->findOneById($b->getUserUuid()),
        ], $bookings);

        return $this->render('admin/booking/reservations.html.twig', [
            'resource' => $resource,
            'rows' => $rows,
            'csrf_token_cancel' => self::CSRF_TOKEN_CANCEL,
        ]);
    }

    #[Route(path: '/reservation/{id}/cancel', name: '_reservation_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancelReservation(Request $request, Booking $booking): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_CANCEL, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user = $this->userRepo->findOneById($booking->getUserUuid());
        if (!$user instanceof User) {
            $this->addFlash('error', 'User der Buchung konnte nicht gefunden werden.');

            return $this->redirectToRoute('admin_booking_reservations', ['id' => $booking->getResource()->getId()]);
        }

        try {
            $this->bookingService->cancelBooking($booking, $user, true);
            $this->addFlash('success', 'Buchung wurde storniert.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Fehler beim Stornieren der Buchung: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_booking_reservations', ['id' => $booking->getResource()->getId()]);
    }
}
