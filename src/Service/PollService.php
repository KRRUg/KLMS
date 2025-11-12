<?php

namespace App\Service;

use App\Entity\Poll;
use App\Entity\PollOption;
use App\Entity\PollVote;
use App\Exception\PollVoteException;
use App\Repository\PollRepository;
use App\Repository\PollVoteRepository;
use App\Security\LoginUser;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PollService
{
    private readonly PollRepository $pollRepository;
    private readonly PollVoteRepository $voteRepository;
    private readonly EntityManagerInterface $entityManager;
    private readonly Security $security;
    private readonly RequestStack $requestStack;
    private readonly string $appSecret;

    public function __construct(
        PollRepository $pollRepository,
        PollVoteRepository $voteRepository,
        EntityManagerInterface $entityManager,
        Security $security,
        RequestStack $requestStack,
        #[Autowire('%kernel.secret%')] string $appSecret
    ) {
        $this->pollRepository = $pollRepository;
        $this->voteRepository = $voteRepository;
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->requestStack = $requestStack;
        $this->appSecret = $appSecret;
    }

    public function getActivePoll(): ?Poll
    {
        return $this->pollRepository->findActive(new DateTimeImmutable());
    }

    public function getPollState(?Poll $poll): ?array
    {
        if (!$poll) {
            return null;
        }

        $context = $this->buildContext();
        $existingVote = $this->voteRepository->findExistingVote($poll, $context['userUuid'], $context['fingerprint']);
        $hasVoted = $existingVote !== null;
        $isClosed = !$poll->isActive($context['now']);

        return [
            'poll' => $this->serializePoll($poll),
            'hasVoted' => $hasVoted,
            'isClosed' => $isClosed,
            'isAuthenticated' => $context['isAuthenticated'],
            'results' => ($hasVoted || $isClosed) ? $this->buildResults($poll, $existingVote) : null,
        ];
    }

    public function submitVote(Poll $poll, PollOption $option): PollVote
    {
        $context = $this->buildContext();

        $existing = $this->voteRepository->findExistingVote($poll, $context['userUuid'], $context['fingerprint']);
        if ($existing) {
            throw PollVoteException::alreadyVoted();
        }

        $vote = new PollVote();
        $vote->setPoll($poll)
            ->setOption($option)
            ->setUserUuid($context['userUuid'])
            ->setFingerprintHash($context['fingerprint'])
            ->setCreatedAt($context['now']);

        $this->entityManager->persist($vote);
        $this->entityManager->flush();

        return $vote;
    }

    private function serializePoll(Poll $poll): array
    {
        $options = [];
        foreach ($poll->getOptions() as $option) {
            $options[] = [
                'id' => $option->getId(),
                'label' => $option->getLabel(),
            ];
        }

        return [
            'id' => $poll->getId(),
            'question' => $poll->getQuestion(),
            'startAt' => $poll->getStartAt()->format(DATE_ATOM),
            'endAt' => $poll->getEndAt()?->format(DATE_ATOM),
            'onlyRegistered' => $poll->isOnlyRegistered(),
            'options' => $options,
        ];
    }

    private function buildResults(Poll $poll, ?PollVote $existingVote): array
    {
        $counts = $this->voteRepository->getVoteCounts($poll);
        $total = array_sum($counts);
        $selectedId = $existingVote?->getOption()->getId();

        $options = [];
        foreach ($poll->getOptions() as $option) {
            $optionId = $option->getId();
            $votes = $counts[$optionId] ?? 0;
            $percentage = $total > 0 ? round(($votes / $total) * 100, 1) : 0.0;
            $options[] = [
                'id' => $optionId,
                'label' => $option->getLabel(),
                'votes' => $votes,
                'percentage' => $percentage,
                'isSelected' => $selectedId === $optionId,
            ];
        }

        return [
            'total' => $total,
            'options' => $options,
        ];
    }

    /**
     * @return array{userUuid: ?string, fingerprint: string, now: DateTimeImmutable, isAuthenticated: bool}
     */
    private function buildContext(): array
    {
        $now = new DateTimeImmutable();
        $request = $this->requestStack->getCurrentRequest();

        $loginUser = $this->security->getUser();
        $userUuid = null;
        $isAuthenticated = $loginUser !== null;
        if ($loginUser instanceof LoginUser) {
            $userUuid = $loginUser->getUser()->getUuid()?->toString();
        }

        $fingerprint = $this->fingerprint($request);

        return [
            'userUuid' => $userUuid,
            'fingerprint' => $fingerprint,
            'now' => $now,
            'isAuthenticated' => $isAuthenticated,
        ];
    }

    private function fingerprint(?Request $request): string
    {
        $ip = $request?->getClientIp() ?? '0.0.0.0';
        $userAgent = substr((string) $request?->headers->get('User-Agent', 'unknown'), 0, 255);
        $acceptLanguage = substr((string) $request?->headers->get('Accept-Language', 'unknown'), 0, 255);

        return hash('sha256', implode('|', [$this->appSecret, $ip, $userAgent, $acceptLanguage]));
    }
}
