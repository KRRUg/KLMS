<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\GamerService;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class GamerExtension extends AbstractExtension
{
    private GamerService $gamerService;

    public function __construct(GamerService $gamerService)
    {
        $this->gamerService = $gamerService;
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [
            new TwigTest('registered_gamer', [$this, 'gamerIsRegistered']),
            new TwigTest('paid_gamer', [$this, 'gamerIsPaid']),
            new TwigTest('seated_gamer', [$this, 'gamerIsSeated']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('seat', [$this, 'getSeat']),
        ];
    }

    public function gamerIsRegistered(User $user): bool
    {
        return $this->gamerService->gamerHasRegistered($user);
    }

    public function gamerIsPaid(User $user): bool
    {
        return $this->gamerService->gamerHasPaid($user);
    }

    public function gamerIsSeated(User $user): bool
    {
        return false; // TODO implement me
    }

    public function getSeat(User $user): ?string
    {
        return null; // TODO implement me
    }
}
