<?php
namespace App\EventListener;

use App\Entity\TourneyGame;
use App\Entity\TourneyStage;
use App\Service\PushNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: TourneyGame::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: TourneyGame::class)]
class TourneyGameListener
{
    public function __construct(
        private PushNotificationService $pushNotificationService,
        private LoggerInterface $logger
    ) {
    }

    public function postPersist(TourneyGame $game, PostPersistEventArgs $event): void
    {
        $this->notifyIfReady($game);
    }

    public function preUpdate(TourneyGame $game, PreUpdateEventArgs $event): void
    {
        // In Bracket-Turnieren werden Teams erst nachträglich per Update gesetzt (z.B. Gewinner
        // rückt ins nächste Spiel auf), nicht beim Anlegen des Spiels selbst.
        if (!$event->hasChangedField('teamA') && !$event->hasChangedField('teamB')) {
            return;
        }
        $this->notifyIfReady($game);
    }

    public function notifyIfReady(TourneyGame $game, ?array $onlyGamerUuids = null): void
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

        if ($onlyGamerUuids !== null) {
            $playerUuidMap = array_intersect_key($playerUuidMap, array_flip($onlyGamerUuids));
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
            $this->logger->error('Failed to send tourney game push notification', [
                'gameId' => $game->getId(),
                'exception' => $e,
            ]);
        }
    }
}


