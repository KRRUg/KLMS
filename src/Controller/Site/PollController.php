<?php

namespace App\Controller\Site;

use App\Controller\BaseController;
use App\Entity\Poll;
use App\Entity\PollOption;
use App\Exception\PollVoteException;
use App\Service\PollService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Serializer\SerializerInterface;

class PollController extends BaseController
{
    private readonly PollService $pollService;
    private readonly EntityManagerInterface $entityManager;
    private readonly CsrfTokenManagerInterface $csrfTokenManager;
    private readonly RateLimiterFactory $pollVoteLimiter;

    public function __construct(
        SerializerInterface $serializer,
        PollService $pollService,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        RateLimiterFactory $pollVoteLimiter
    ) {
        parent::__construct($serializer);
        $this->pollService = $pollService;
        $this->entityManager = $entityManager;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->pollVoteLimiter = $pollVoteLimiter;
    }

    #[Route(path: '/poll', name: 'poll_current', methods: ['GET'])]
    public function current(): JsonResponse
    {
        $poll = $this->pollService->getActivePoll();
        $payload = $this->pollService->getPollState($poll);

        return $this->apiResponse($payload);
    }

    #[Route(path: '/poll/csrf-token', name: 'poll_csrf_token', methods: ['GET'])]
    public function csrfToken(): JsonResponse
    {
        $token = $this->csrfTokenManager->getToken('poll_vote');

        return $this->apiResponse([
            'token' => $token->getValue(),
        ]);
    }

    #[Route(path: '/poll/vote', name: 'poll_vote', methods: ['POST'])]
    public function vote(Request $request): JsonResponse
    {
        // Rate Limiting
        $limiter = $this->pollVoteLimiter->create($request->getClientIp() ?? 'unknown');
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            return $this->apiResponse([
                'Error' => [
                    'message' => 'Zu viele Abstimmungsversuche. Bitte versuche es später erneut.',
                    'retryAfter' => $limit->getRetryAfter()->getTimestamp(),
                ]
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $body = json_decode((string) $request->getContent(), true);
        if (!is_array($body)) {
            return $this->apiError('Ungültige Anfrage.', Response::HTTP_BAD_REQUEST);
        }

        // CSRF Token Validierung
        $csrfToken = $body['csrfToken'] ?? '';
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('poll_vote', $csrfToken))) {
            return $this->apiError('Ungültiges CSRF-Token.', Response::HTTP_FORBIDDEN);
        }

        $pollId = (int) ($body['pollId'] ?? 0);
        $optionId = (int) ($body['optionId'] ?? 0);

        /** @var Poll|null $poll */
        $poll = $this->entityManager->getRepository(Poll::class)->find($pollId);
        if (!$poll) {
            return $this->apiError('Umfrage nicht gefunden.', Response::HTTP_NOT_FOUND);
        }

        if ($poll->isOnlyRegistered() && !$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->apiError('Bitte melde dich an, um abzustimmen.', Response::HTTP_FORBIDDEN);
        }

        if (!$poll->isActive()) {
            return $this->apiError('Die Abstimmung ist beendet.', Response::HTTP_GONE);
        }

        /** @var PollOption|null $option */
        $option = $this->entityManager->getRepository(PollOption::class)->find($optionId);
        if (!$option || $option->getPoll()?->getId() !== $poll->getId()) {
            return $this->apiError('Antwort ungültig.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->pollService->submitVote($poll, $option);
        } catch (PollVoteException $exception) {
            if ($exception->getMessage() === PollVoteException::CODE_ALREADY_VOTED) {
                return $this->apiError('Du hast bereits abgestimmt.', Response::HTTP_CONFLICT);
            }

            return $this->apiError('Abstimmung nicht möglich.', Response::HTTP_BAD_REQUEST);
        }

        $payload = $this->pollService->getPollState($poll);

        return $this->apiResponse($payload);
    }
}
