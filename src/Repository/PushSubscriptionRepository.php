<?php

namespace App\Repository;

use App\Entity\PushSubscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PushSubscription> */
class PushSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PushSubscription::class);
    }

    /** @return list<PushSubscription> */
    public function findActiveForUser(User $user): array
    {
        return $this->createQueryBuilder('subscription')
            ->andWhere('subscription.user = :user')->andWhere('subscription.revokedAt IS NULL')
            ->setParameter('user', $user)->getQuery()->getResult();
    }

    public function findByEndpoint(string $endpoint): ?PushSubscription
    {
        return $this->findOneBy(['endpointHash' => hash('sha256', trim($endpoint))]);
    }
}
