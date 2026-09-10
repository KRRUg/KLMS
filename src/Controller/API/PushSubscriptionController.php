<?php
namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\PushSubscription;
use App\Security\LoginUser;

#[Route('/push-subscription')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class PushSubscriptionController extends AbstractController
{
    #[Route('/subscribe', name: 'api_push_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['endpoint'])) {
            return new JsonResponse(['error' => 'Invalid subscription'], 400);
        }
        
        if (mb_strlen($data['endpoint']) > 500) {
            return new JsonResponse(['error' => 'Invalid subscription'], 400);
        }

        /** @var LoginUser $user */
        $user = $this->getUser();
        $gamerUuid = $user->getUser()->getUuid();

        // Check if subscription already exists
        $repo = $em->getRepository(PushSubscription::class);
        $existing = $repo->findOneBy(['endpoint' => $data['endpoint']]);
        if ($existing) {
            // Gleicher Endpoint, aber anderer eingeloggter User (z.B. Shared Device):
            // Abo auf den aktuellen User ummelden, sonst bekommt der alte Owner weiter Nachrichten.
            if (!$gamerUuid->equals($existing->getGamer())) {
                $existing->setGamer($gamerUuid);
                $em->flush();
            }
            return new JsonResponse(['success' => true, 'message' => 'Already subscribed']);
        }

        $subscription = new PushSubscription();
        $subscription->setEndpoint($data['endpoint']);
        $subscription->setPublicKey($data['keys']['p256dh'] ?? null);
        $subscription->setAuthToken($data['keys']['auth'] ?? null);
        // Browsers never include contentEncoding in PushSubscription.toJSON(); modern
        // push services only support aes128gcm (RFC 8291), the legacy aesgcm draft is dead.
        $subscription->setContentEncoding($data['contentEncoding'] ?? 'aes128gcm');
        $subscription->setGamer($gamerUuid);

        $em->persist($subscription);
        $em->flush();
        return new JsonResponse(['success' => true]);
    }

    #[Route('/unsubscribe', name: 'api_push_unsubscribe', methods: ['POST'])]
    public function unsubscribe(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['endpoint'])) {
            return new JsonResponse(['error' => 'Invalid subscription'], 400);
        }

        /** @var LoginUser $user */
        $user = $this->getUser();

        $repo = $em->getRepository(PushSubscription::class);
        // Nur das eigene Abo darf gelöscht werden, sonst könnte jeder eingeloggte
        // User fremde Push-Subscriptions per bekanntem Endpoint deaktivieren (IDOR).
        $subscription = $repo->findOneBy([
            'endpoint' => $data['endpoint'],
            'gamer' => $user->getUser()->getUuid(),
        ]);
        if ($subscription) {
            $em->remove($subscription);
            $em->flush();
        }
        return new JsonResponse(['success' => true]);
    }
}
