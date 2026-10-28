<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Entity\UserActivity;
use App\Repository\UserActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class UserActivitySubscriber implements EventSubscriberInterface
{
    private const UPDATE_INTERVAL_SECONDS = 300;

    public function __construct(
        private Security $security,
        private UserActivityRepository $activities,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['recordActivity', -10]];
    }

    public function recordActivity(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->isActive()) {
            return;
        }

        $now = new \DateTimeImmutable();
        $lastActiveAt = $user->getLastActiveAt();
        if ($lastActiveAt !== null && $now->getTimestamp() - $lastActiveAt->getTimestamp() < self::UPDATE_INTERVAL_SECONDS) {
            return;
        }

        $today = $now->setTime(0, 0);
        $activity = $this->activities->findOneBy(['user' => $user, 'activityDate' => $today]);
        if (!$activity instanceof UserActivity) {
            $activity = (new UserActivity())
                ->setUser($user)
                ->setCompany($user->getCompany())
                ->setActivityDate($today);
            $this->entityManager->persist($activity);
        }

        $user->recordActivity($now);
        $activity->markSeen($now);
        $this->entityManager->flush();
    }
}
