<?php
namespace App\EventListener;

use App\Entity\TourneyGame;
use App\Entity\TourneyStage;
use App\Service\PushNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: TourneyGame::class)]
class TourneyGameListener
{
    public function __construct(
        private PushNotificationService $pushNotificationService
    ) {
    }

    public function postPersist(TourneyGame $game, PostPersistEventArgs $event): void
    {
        $tourney = $game->getTourney();
        if (!$tourney || $tourney->getStatus() !== TourneyStage::Running) {
            return;
        }

        $teamA = $game->getTeamA();
        $teamB = $game->getTeamB();
        
        if (!$teamA || !$teamB) {
            return;
        }

        $playerUuidMap = [];
        foreach ([$teamA, $teamB] as $team) {
            foreach ($team->getUserUuids() as $uuid) {
                if ($uuid) {
                    $playerUuidMap[$uuid->toString()] = $uuid;
                }
            }
        }

        if (empty($playerUuidMap)) {
            return;
        }

        $url = '/tourney/' . $tourney->getId();
        $message = sprintf(
            'Du bist dran bei "%s": %s vs. %s',
            $tourney->getName() ?? '',
            $teamA->getName() ?? '',
            $teamB->getName() ?? ''
        );
        
        try {
            $this->pushNotificationService->notifyTourneyPlayers(
                $tourney->getName() ?? 'Turnier',
                $tourney->getId(),
                $message,
                $url,
                array_values($playerUuidMap)
            );
        } catch (\Throwable $e) {
            // Fehler ignorieren, um den Spielablauf nicht zu stören
        }
    }
}

