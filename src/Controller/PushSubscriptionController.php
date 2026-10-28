<?php

namespace App\Controller;

use App\Entity\PushSubscription;
use App\Entity\User;
use App\Repository\PushSubscriptionRepository;
use App\Service\Notification\WebPushSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/push', name: 'push_')]
#[IsGranted('ROLE_USER')]
final class PushSubscriptionController extends AbstractController
{
    #[Route('/status', name: 'status', methods: ['GET'])]
    public function status(PushSubscriptionRepository $subscriptions, WebPushSender $sender): JsonResponse
    {
        $user = $this->requireUser();

        return $this->json(['configured' => $sender->isConfigured(), 'subscribed' => $subscriptions->findActiveForUser($user) !== []]);
    }

    #[Route('/subscribe', name: 'subscribe', methods: ['POST'])]
    public function subscribe(Request $request, PushSubscriptionRepository $subscriptions, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->requireUser();
        if (!$this->isCsrfTokenValid('push_subscription', (string) $request->headers->get('X-CSRF-Token'))) {
            throw $this->createAccessDeniedException();
        }

        $payload = $request->toArray();
        $endpoint = trim((string) ($payload['endpoint'] ?? ''));
        $keys = is_array($payload['keys'] ?? null) ? $payload['keys'] : [];
        $publicKey = trim((string) ($keys['p256dh'] ?? ''));
        $authToken = trim((string) ($keys['auth'] ?? ''));
        if ($endpoint === '' || $publicKey === '' || $authToken === '') {
            return $this->json(['message' => 'Pretplata nije potpuna.'], 422);
        }

        $subscription = $subscriptions->findByEndpoint($endpoint) ?? new PushSubscription();
        $subscription->setUser($user)->setEndpoint($endpoint)->setPublicKey($publicKey)->setAuthToken($authToken)
            ->setContentEncoding(isset($payload['contentEncoding']) ? (string) $payload['contentEncoding'] : null)
            ->setUserAgent($request->headers->get('User-Agent'))->activate();
        $entityManager->persist($subscription);
        $entityManager->flush();

        return $this->json(['subscribed' => true]);
    }

    #[Route('/unsubscribe', name: 'unsubscribe', methods: ['POST'])]
    public function unsubscribe(Request $request, PushSubscriptionRepository $subscriptions, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->requireUser();
        if (!$this->isCsrfTokenValid('push_subscription', (string) $request->headers->get('X-CSRF-Token'))) {
            throw $this->createAccessDeniedException();
        }

        $endpoint = trim((string) ($request->toArray()['endpoint'] ?? ''));
        $subscription = $endpoint !== '' ? $subscriptions->findByEndpoint($endpoint) : null;
        if ($subscription instanceof PushSubscription && $subscription->getUser()?->getId() === $user->getId()) {
            $subscription->revoke();
            $entityManager->flush();
        }

        return $this->json(['subscribed' => false]);
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
