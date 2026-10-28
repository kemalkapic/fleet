<?php

namespace App\Repository;

use App\Entity\UserActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<UserActivity> */
class UserActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserActivity::class);
    }

    /** @return list<UserActivity> */
    public function findPlatformBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('activity')
            ->addSelect('user', 'company')
            ->innerJoin('activity.user', 'user')
            ->innerJoin('activity.company', 'company')
            ->andWhere('activity.activityDate BETWEEN :from AND :to')
            ->setParameter('from', $from->setTime(0, 0))
            ->setParameter('to', $to->setTime(0, 0))
            ->orderBy('activity.activityDate', 'ASC')
            ->addOrderBy('activity.lastSeenAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
