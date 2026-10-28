<?php

namespace App\Service\Notification;

use App\Entity\Notification;
use App\Entity\PushSubscription;
use App\Entity\UserNotification;
use App\Repository\NotificationRepository;
use App\Repository\PushSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

final readonly class WebPushSender
{
    public function __construct(
        private PushSubscriptionRepository $subscriptions,
        private NotificationRepository $deliveryLogs,
        private EntityManagerInterface $entityManager,
        private string $webPushPublicKey,
        private string $webPushPrivateKey,
        private string $webPushSubject,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->webPushPublicKey !== '' && $this->webPushPrivateKey !== '' && $this->webPushSubject !== '';
    }

    public function send(UserNotification $item, ?int $triggerDaysBefore = null): bool
    {
        $user = $item->getUser();
        $company = $item->getCompany();
        if (!$this->isConfigured() || $user === null || $company === null) {
            return false;
        }

        $recipient = 'user:'.$user->getId();
        if ($this->deliveryLogs->existsForSentEvent($company, $item->getEventKey(), Notification::CHANNEL_WEB_PUSH, $recipient)) {
            return true;
        }

        $subscriptions = $this->subscriptions->findActiveForUser($user);
        if ($subscriptions === []) {
            return false;
        }

        $webPush = new WebPush(['VAPID' => [
            'subject' => $this->webPushSubject,
            'publicKey' => $this->webPushPublicKey,
            'privateKey' => $this->webPushPrivateKey,
        ]]);
        $payload = json_encode([
            'title' => $item->getTitle(),
            'body' => $item->getMessageText(),
            'url' => '/notifications/'.$item->getId().'/open',
            'icon' => '/assets/images/app-icon-192.png',
            'badge' => '/assets/images/favicon-32.png',
            'tag' => $item->getEventKey(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $sent = false;
        $failures = [];
        foreach ($subscriptions as $storedSubscription) {
            try {
                $report = $webPush->sendOneNotification($this->toWebPushSubscription($storedSubscription), $payload);
                if ($report->isSuccess()) {
                    $sent = true;
                    continue;
                }

                $failures[] = $report->getReason();
                if ($report->isSubscriptionExpired()) {
                    $storedSubscription->revoke();
                }
            } catch (\Throwable $exception) {
                $failures[] = $exception->getMessage();
            }
        }

        $log = (new Notification())
            ->setCompany($company)
            ->setDocument($item->getDocument())
            ->setInboxItem($item)
            ->setChannel(Notification::CHANNEL_WEB_PUSH)
            ->setTriggerDaysBefore($triggerDaysBefore)
            ->setRecipient($recipient)
            ->setMessageText($item->getMessageText())
            ->setEventKey($item->getEventKey());

        $sent ? $log->markSent() : $log->markFailed(implode(' | ', array_unique($failures)) ?: 'Web push nije isporučen.');
        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $sent;
    }

    private function toWebPushSubscription(PushSubscription $subscription): Subscription
    {
        return Subscription::create([
            'endpoint' => $subscription->getEndpoint(),
            'publicKey' => $subscription->getPublicKey(),
            'authToken' => $subscription->getAuthToken(),
            'contentEncoding' => $subscription->getContentEncoding() ?? 'aes128gcm',
        ]);
    }
}
