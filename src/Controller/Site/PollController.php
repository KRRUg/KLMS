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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class PollController extends BaseController
{
    private readonly PollService $pollService;
    private readonly EntityManagerInterface $entityManager;

    public function __construct(SerializerInterface $serializer, PollService $pollService, EntityManagerInterface $entityManager)
    {
        parent::__construct($serializer);
        $this->pollService = $pollService;
        $this->entityManager = $entityManager;
    }

    #[Route(path: '/poll', name: 'poll_current', methods: ['GET'])]
    public function current(): JsonResponse
    {
        $poll = $this->pollService->getActivePoll();
        $payload = $this->pollService->getPollState($poll);

        return $this->apiResponse($payload);
    }

    #[Route(path: '/poll/vote', name: 'poll_vote', methods: ['POST'])]
    public function vote(Request $request): JsonResponse
    {
        $body = json_decode((string) $request->getContent(), true);
        if (!is_array($body)) {
            return $this->apiError('Ungültige Anfrage.', Response::HTTP_BAD_REQUEST);
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
