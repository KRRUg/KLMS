<?php
namespace App\Service;

use App\Entity\PushSubscription;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class PushNotificationService
{
    private EntityManagerInterface $em;
    private array $vapidAuth;
    private string $notificationTitle;

    public function __construct(
        EntityManagerInterface $em,
        string $vapidSubject,
        string $vapidPublicKey,
        string $vapidPrivateKey,
        SettingService $settingService
    )
    {
        $this->em = $em;
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
        ?array $targetGamers = null,
        bool $flushExpiredSubscriptions = false
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
        
        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess() && $report->isSubscriptionExpired()) {
                $endpoint = $report->getEndpoint();
                $expiredSub = $this->em->getRepository(PushSubscription::class)
                    ->findOneBy(['endpoint' => $endpoint]);
                if ($expiredSub) {
                    $this->em->remove($expiredSub);
                }
            }
        }
        if ($flushExpiredSubscriptions) {
            $this->em->flush();
        }
    }
}
