<?php

namespace App\Service;

use App\Repository\TourneyRepository;
use Doctrine\ORM\EntityManagerInterface;

class TourneyService extends OptimalService
{
    private readonly EntityManagerInterface $em;
    private readonly TourneyRepository $repository;

    // TODO remove unnecessary Tourney repositories
    public function __construct(
        TourneyRepository $repository,
        SettingService $settings,
        EntityManagerInterface $em,
    ) {
        parent::__construct($settings);
        $this->repository = $repository;
        $this->em = $em;
    }

    protected static function getSettingKey(): string
    {
        return 'tourney.enabled';
    }


}