<?php

namespace App\Controller\Site;

use App\Controller\BaseController;
use App\Controller\LoginUserTrait;
use App\Entity\Booking;
use App\Entity\BookingResource;
use App\Exception\BookingException;
use App\Service\BookingService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route(path: '/booking', name: 'booking')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class BookingController extends BaseController
{
    use LoginUserTrait;

    private const CSRF_TOKEN_ID = 'booking_action';

    private readonly BookingService $bookingService;
    private readonly CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(
        SerializerInterface $serializer,
        BookingService $bookingService,
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        parent::__construct($serializer);
        $this->bookingService = $bookingService;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    #[Route(path: '', name: '', methods: ['GET'])]
    public function index(): Response
    {
        if (!$this->bookingService->active()) {
            throw $this->createNotFoundException();
        }

        $user = $this->requireDomainUser();
        $resources = $this->bookingService->getActiveResources();
        $myBookings = $this->bookingService->getUserBookings($user);

        return $this->render('site/booking/index.html.twig', [
            'resources' => $resources,
            'myBookings' => $myBookings,
            'mayBook' => $this->bookingService->userMayBook($user),
        ]);
    }

    #[Route(path: '/{id}', name: '_resource', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function resource(BookingResource $resource): Response
    {
        if (!$this->bookingService->active()) {
            throw $this->createNotFoundException();
        }

        if (!$resource->isActive()) {
            $this->addFlash('error', 'Diese Ressource ist nicht verfügbar.');

            return $this->redirectToRoute('booking');
        }

        $user = $this->requireDomainUser();
        $slots = $this->bookingService->getAvailability($resource);

        return $this->render('site/booking/resource.html.twig', [
            'resource' => $resource,
            'slots' => $slots,
            'mayBook' => $this->bookingService->userMayBook($user),
        ]);
    }

    #[Route(path: '/csrf-token', name: '_csrf_token', methods: ['GET'])]
    public function csrfToken(): JsonResponse
    {
        $token = $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);

        return $this->apiResponse([
            'token' => $token->getValue(),
        ]);
    }

    #[Route(path: '/{id}/availability', name: '_availability', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function availability(Request $request, BookingResource $resource): JsonResponse
    {
        if (!$this->bookingService->active() || !$resource->isActive()) {
            return $this->apiError('Ressource ist nicht verfügbar.', Response::HTTP_NOT_FOUND);
        }

        $from = $this->parseDate($request->query->get('from'));
        $to = $this->parseDate($request->query->get('to'));

        $slots = $this->bookingService->getAvailability($resource, $from, $to);

        $payload = array_map(static fn(array $slot) => [
            'start' => $slot['start']->format(DATE_ATOM),
            'end' => $slot['end']->format(DATE_ATOM),
            'free' => $slot['free'],
            'total' => $slot['total'],
            'bufferMinutes' => $resource->getBufferMinutes(),
        ], $slots);

        return $this->apiResponse($payload, true);
    }

    #[Route(path: '/{id}/book', name: '_book', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function book(Request $request, BookingResource $resource): Response
    {
        $isJson = $this->isJsonRequest($request);

        if (!$this->bookingService->active()) {
            if ($isJson) {
                return $this->apiError('Buchungssystem ist nicht verfügbar.', Response::HTTP_NOT_FOUND);
            }

            throw $this->createNotFoundException();
        }

        if ($isJson) {
            $body = json_decode((string) $request->getContent(), true);
            if (!is_array($body)) {
                return $this->apiError('Ungültige Anfrage.', Response::HTTP_BAD_REQUEST);
            }

            $csrfToken = (string) ($body['csrfToken'] ?? '');
            $start = $this->parseDate($body['start'] ?? null);
        } else {
            $csrfToken = (string) $request->request->get('_token', '');
            $start = $this->parseDate($request->request->get('start'));
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $csrfToken))) {
            if ($isJson) {
                return $this->apiError('Ungültiges CSRF-Token.', Response::HTTP_FORBIDDEN);
            }

            $this->addFlash('error', 'Ungültiges CSRF-Token.');

            return $this->redirectToRoute('booking');
        }

        if (!$start) {
            if ($isJson) {
                return $this->apiError('Ungültiger Zeitpunkt.', Response::HTTP_BAD_REQUEST);
            }

            $this->addFlash('error', 'Ungültiger Zeitpunkt.');

            return $this->redirectToRoute('booking');
        }

        $user = $this->requireDomainUser();

        try {
            $booking = $this->bookingService->bookSlot($resource, $start, $user);
        } catch (BookingException $e) {
            $message = $this->translateBookingError($e);
            if ($isJson) {
                return $this->apiError($message, Response::HTTP_CONFLICT);
            }

            $this->addFlash('error', $message);

            return $this->redirectToRoute('booking');
        }

        if (!$isJson) {
            $this->addFlash('success', sprintf('Buchung erfolgreich erstellt. Zugeteilte Einheit: %s', $resource->getUnitLabel($booking->getUnitNumber()), $booking->getUnitNumber()));

            return $this->redirectToRoute('booking');
        }

        return $this->apiResponse([
            'id' => $booking->getId(),
            'unitNumber' => $booking->getUnitNumber(),
            'unitLabel' => $resource->getUnitLabel($booking->getUnitNumber()),
            'start' => $booking->getStartAt()->format(DATE_ATOM),
            'end' => $booking->getEndAt()->format(DATE_ATOM),
        ]);
    }

    #[Route(path: '/reservation/{id}/cancel', name: '_reservation_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancel(Request $request, Booking $booking): Response
    {
        $isJson = $this->isJsonRequest($request);

        if ($isJson) {
            $body = json_decode((string) $request->getContent(), true);
            $csrfToken = (string) (is_array($body) ? ($body['csrfToken'] ?? '') : '');
        } else {
            $csrfToken = (string) $request->request->get('_token', '');
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $csrfToken))) {
            if ($isJson) {
                return $this->apiError('Ungültiges CSRF-Token.', Response::HTTP_FORBIDDEN);
            }

            $this->addFlash('error', 'Ungültiges CSRF-Token.');

            return $this->redirectToRoute('booking');
        }

        $user = $this->requireDomainUser();

        try {
            $this->bookingService->cancelBooking($booking, $user);
        } catch (BookingException $e) {
            $message = $this->translateBookingError($e);
            if ($isJson) {
                return $this->apiError($message, Response::HTTP_CONFLICT);
            }

            $this->addFlash('error', $message);

            return $this->redirectToRoute('booking');
        }

        if (!$isJson) {
            $this->addFlash('success', 'Buchung wurde storniert.');

            return $this->redirectToRoute('booking');
        }

        return $this->apiResponse(['cancelled' => true]);
    }

    private function isJsonRequest(Request $request): bool
    {
        if ($request->getContentTypeFormat() === 'json') {
            return true;
        }

        return str_contains((string) $request->headers->get('Accept', ''), 'application/json');
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    private function translateBookingError(BookingException $e): string
    {
        return match ($e->getMessage()) {
            BookingException::CODE_SLOT_FULL => 'Dieser Zeitslot ist bereits ausgebucht.',
            BookingException::CODE_INVALID_SLOT => 'Dieser Zeitpunkt ist nicht buchbar.',
            BookingException::CODE_RESOURCE_INACTIVE => 'Diese Ressource ist nicht verfügbar.',
            BookingException::CODE_LIMIT_REACHED => 'Du hast bereits die maximale Anzahl an Buchungen erreicht.',
            BookingException::CODE_ALREADY_BOOKED_SLOT => 'Diesen Zeitslot hast du bereits gebucht.',
            BookingException::CODE_PAST_BOOKING => 'Vergangene Buchungen können nicht storniert werden.',
            BookingException::CODE_NOT_OWNER => 'Diese Buchung gehört dir nicht.',
            BookingException::CODE_ALREADY_CANCELLED => 'Diese Buchung wurde bereits storniert.',
            BookingException::CODE_NOT_ALLOWED => 'Du erfüllst die Voraussetzungen für eine Buchung nicht.',
            default => 'Buchung nicht möglich.',
        };
    }
}
