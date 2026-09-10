<?php
namespace App\Service;

use App\Entity\PushSubscription;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class PushNotificationService
{
    private EntityManagerInterface $em;
    private array $vapidAuth;
    private string $notificationTitle;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        string $vapidSubject,
        string $vapidPublicKey,
        string $vapidPrivateKey,
        SettingService $settingService,
        LoggerInterface $logger
    )
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->vapidAuth = [
            'VAPID' => [
                'subject' => $vapidSubject,
                'publicKey' => $vapidPublicKey,
                'privateKey' => $vapidPrivateKey,
            ]
        ];
        $siteTitle = $settingService->get('site.title') ?? 'KLMS';
        $this->notificationTitle = trim($siteTitle) . ' Turniersystem';
    }

    /**
     * Sendet eine Push-Nachricht an alle gespeicherten Abos für ein Turnier.
     */
    public function notifyTourneyPlayers(
        string $tourneyName,
        int $tourneyId,
        string $message,
        string $url,
        ?array $targetGamers = null
    ): void
    {
        $repo = $this->em->getRepository(PushSubscription::class);
        if ($targetGamers !== null) {
            $uuids = [];
            foreach ($targetGamers as $gamer) {
                if ($gamer instanceof UuidInterface) {
                    $uuids[] = $gamer;
                } elseif (is_string($gamer) && $gamer !== '') {
                    $uuids[] = Uuid::fromString($gamer);
                }
            }

            if (empty($uuids)) {
                return;
            }

            $subscriptions = $repo->findBy(['gamer' => $uuids]);
        } else {
            $subscriptions = $repo->findAll();
        }

        if (!$subscriptions) {
            return;
        }
        
        $payload = json_encode([
            'title' => $this->notificationTitle,
            'body' => $message,
            'tourneyName' => $tourneyName,
            'tourneyId' => $tourneyId,
            'url' => $url
        ]);
        
        $webPush = new WebPush($this->vapidAuth, [], 30, ['verify' => true]);
        
        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->getEndpoint(),
                'publicKey' => $sub->getPublicKey(),
                'authToken' => $sub->getAuthToken(),
                'contentEncoding' => $sub->getContentEncoding(),
            ]);
            $webPush->queueNotification($subscription, $payload);
        }
        
        $expiredEndpoints = [];
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }
            if ($report->isSubscriptionExpired()) {
                $expiredEndpoints[] = $report->getEndpoint();
            } else {
                $this->logger->warning('Push notification failed', [
                    'endpoint' => $report->getEndpoint(),
                    'reason' => $report->getReason(),
                ]);
            }
        }

        if ($expiredEndpoints) {
            // Direktes DQL-Delete statt em->remove()+flush(): wir werden oft aus einem
            // Doctrine preUpdate/postPersist-Listener heraus aufgerufen, also mitten in
            // einem laufenden Flush eines anderen Entity - ein verschachtelter em->flush()
            // wäre dort unsicher/schlägt fehl.
            $this->em->createQueryBuilder()
                ->delete(PushSubscription::class, 's')
                ->where('s.endpoint IN (:endpoints)')
                ->setParameter('endpoints', $expiredEndpoints)
                ->getQuery()
                ->execute();
        }
    }
}
