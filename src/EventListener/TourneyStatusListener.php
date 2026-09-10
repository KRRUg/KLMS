<?php
namespace App\EventListener;

use App\Entity\Tourney;
use App\Entity\TourneyStage;
use App\Service\PushNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Tourney::class)]
class TourneyStatusListener
{
    public function __construct(
        private PushNotificationService $pushNotificationService,
        private TourneyGameListener $tourneyGameListener,
        private LoggerInterface $logger
    ) {
    }

    public function preUpdate(Tourney $tourney, PreUpdateEventArgs $event): void
    {
        if (!$event->hasChangedField('status')) {
            return;
        }

        $oldStatus = $event->getOldValue('status');
        $newStatus = $event->getNewValue('status');

        // Doctrine gibt Enum-Werte als Integer zurück in preUpdate
        if ($oldStatus === TourneyStage::Seeding->value && $newStatus === TourneyStage::Running->value) {
            $url = '/tourney/' . $tourney->getId();
            $message = sprintf('Das Turnier "%s" hat begonnen! Viel Erfolg!', $tourney->getName() ?? '');
            
            try {
                $this->pushNotificationService->notifyTourneyPlayers(
                    $tourney->getName() ?? 'Turnier',
                    $tourney->getId(),
                    $message,
                    $url
                );

                // Spiele (z.B. Gruppenphase) wurden bereits beim Seeding angelegt, als das
                // Turnier noch nicht "Running" war - daher hier zusätzlich pro Spiel benachrichtigen.
                // Bei Round-Robin-Gruppenphasen kann ein Team mehrere Spiele gleichzeitig offen
                // haben, das ist normal - daher jedes offene Spiel einzeln benachrichtigen
                // (die Seite zeigt jetzt ebenfalls alle offenen Spiele an, siehe TourneyController).
                foreach ($tourney->getGames() as $game) {
                    if ($game->isPending()) {
                        $this->tourneyGameListener->notifyIfReady($game);
                    }
                }
            } catch (\Throwable $e) {
                // Fehler ignorieren, um den Spielablauf nicht zu stören
                // WICHTIG: Exception NICHT re-throwen, sonst wird das Update blockiert
                $this->logger->error('Failed to send tourney start push notification', [
                    'tourneyId' => $tourney->getId(),
                    'exception' => $e,
                ]);
            }
        }
    }
}
