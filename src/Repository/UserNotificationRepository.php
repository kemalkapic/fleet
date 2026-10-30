<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<UserNotification> */
class UserNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotification::class);
    }

    /** @return list<UserNotification> */
    public function findLatestForUser(User $user, int $limit = 8): array
    {
        return $this->createQueryBuilder('item')
            ->leftJoin('item.deliveryLogs', 'deliveryLog')->addSelect('deliveryLog')
            ->leftJoin('item.document', 'document')->addSelect('document')
            ->andWhere('item.user = :user')->setParameter('user', $user)
            ->orderBy('item.createdAt', 'DESC')->addOrderBy('item.id', 'DESC')
            ->setMaxResults($limit)->getQuery()->getResult();
    }

    public function countUnreadForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('item')->select('COUNT(item.id)')
            ->andWhere('item.user = :user')->andWhere('item.readAt IS NULL')
            ->setParameter('user', $user)->getQuery()->getSingleScalarResult();
    }

    public function findOneForUser(int $id, User $user): ?UserNotification
    {
        return $this->findOneBy(['id' => $id, 'user' => $user]);
    }

    public function markAllRead(User $user): int
    {
        return $this->createQueryBuilder('item')->update()
            ->set('item.readAt', ':now')->andWhere('item.user = :user')->andWhere('item.readAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())->setParameter('user', $user)
            ->getQuery()->execute();
    }
}
