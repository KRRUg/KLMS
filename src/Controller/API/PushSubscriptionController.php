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
        
        // Check if subscription already exists
        $repo = $em->getRepository(PushSubscription::class);
        $existing = $repo->findOneBy(['endpoint' => $data['endpoint']]);
        if ($existing) {
            return new JsonResponse(['success' => true, 'message' => 'Already subscribed']);
        }
        
        /** @var LoginUser $user */
        $user = $this->getUser();
        
        $subscription = new PushSubscription();
        $subscription->setEndpoint($data['endpoint']);
        $subscription->setPublicKey($data['keys']['p256dh'] ?? null);
        $subscription->setAuthToken($data['keys']['auth'] ?? null);
        $subscription->setContentEncoding($data['contentEncoding'] ?? 'aesgcm');
        $subscription->setGamer($user->getUser()->getUuid());

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
        $repo = $em->getRepository(PushSubscription::class);
        $subscription = $repo->findOneBy(['endpoint' => $data['endpoint']]);
        if ($subscription) {
            $em->remove($subscription);
            $em->flush();
        }
        return new JsonResponse(['success' => true]);
    }
}
