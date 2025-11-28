<?php
namespace App\EventListener;

use App\Entity\Tourney;
use App\Entity\TourneyStage;
use App\Service\PushNotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Tourney::class)]
class TourneyStatusListener
{
    public function __construct(
        private PushNotificationService $pushNotificationService
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
            } catch (\Throwable $e) {
                // Fehler ignorieren, um den Spielablauf nicht zu stören
                // WICHTIG: Exception NICHT re-throwen, sonst wird das Update blockiert
            }
        }
    }
}
