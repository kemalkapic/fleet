<?php

namespace App\Service\Notification;

use App\Entity\Company;
use App\Entity\FleetDocument;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Repository\UserNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class NotificationInbox
{
    public function __construct(
        private UserNotificationRepository $items,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function create(
        User $user,
        Company $company,
        string $eventKey,
        string $title,
        string $message,
        ?string $targetUrl = null,
        ?FleetDocument $document = null,
    ): UserNotification {
        $existing = $this->items->findOneBy(['user' => $user, 'eventKey' => $eventKey]);
        if ($existing instanceof UserNotification) {
            return $existing;
        }

        $item = (new UserNotification())
            ->setUser($user)
            ->setCompany($company)
            ->setDocument($document)
            ->setEventKey($eventKey)
            ->setTitle($title)
            ->setMessageText($message)
            ->setTargetUrl($targetUrl);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }
}
