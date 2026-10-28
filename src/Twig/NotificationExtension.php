<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\UserNotificationRepository;
use App\Service\Notification\WebPushSender;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class NotificationExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly UserNotificationRepository $notifications,
        private readonly WebPushSender $webPushSender,
        private readonly string $webPushPublicKey,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('notification_inbox', $this->getInbox(...))];
    }

    public function getInbox(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return ['items' => [], 'unreadCount' => 0, 'pushConfigured' => false, 'publicKey' => ''];
        }

        return [
            'items' => $this->notifications->findLatestForUser($user),
            'unreadCount' => $this->notifications->countUnreadForUser($user),
            'pushConfigured' => $this->webPushSender->isConfigured(),
            'publicKey' => $this->webPushPublicKey,
        ];
    }
}
