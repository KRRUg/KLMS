<?php

namespace App\Twig;

use App\Entity\TourneyEntry;
use App\Entity\TourneyEntrySinglePlayer;
use App\Entity\TourneyEntryTeam;
use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TourneyEntryExtension extends AbstractExtension
{
    private readonly IdmRepository $userRepo;

    public function __construct(IdmManager $manager)
    {
        $this->userRepo = $manager->getRepository(User::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('entryName', $this->entryName(...)),
        ];
    }

    public function entryName(?TourneyEntry $entry): string
    {
        if (is_null($entry))
            return "";

        if ($entry instanceof TourneyEntryTeam) {
            return $entry->getName() ?? "";
        } elseif($entry instanceof TourneyEntrySinglePlayer) {
            if (empty($entry->getGamer()))
                return "";
            return $this->userRepo->findOneById($entry->getGamer())->getNickname();
        }
        return "";
    }
}