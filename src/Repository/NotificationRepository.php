<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\FleetDocument;
use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @return list<Notification>
     */
    public function findForCompany(Company $company): array
    {
        return $this->createQueryBuilder('notification')
            ->andWhere('notification.company = :company')
            ->setParameter('company', $company)
            ->orderBy('notification.sentAt', 'DESC')
            ->addOrderBy('notification.createdAt', 'DESC')
            ->addOrderBy('notification.id', 'DESC')
            ->setMaxResults(200)
            ->getQuery()
            ->getResult();
    }

    public function countSentForCompany(Company $company): int
    {
        return (int) $this->createQueryBuilder('notification')
            ->select('COUNT(notification.id)')
            ->andWhere('notification.company = :company')
            ->andWhere('notification.status = :status')
            ->setParameter('company', $company)
            ->setParameter('status', Notification::STATUS_SENT)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{total: int, sent: int, failed: int}
     */
    public function getPlatformStatusCounts(): array
    {
        $result = $this->createQueryBuilder('notification')
            ->select('COUNT(notification.id) AS total')
            ->addSelect('SUM(CASE WHEN notification.status = :sent THEN 1 ELSE 0 END) AS sent')
            ->addSelect('SUM(CASE WHEN notification.status = :failed THEN 1 ELSE 0 END) AS failed')
            ->setParameter('sent', Notification::STATUS_SENT)
            ->setParameter('failed', Notification::STATUS_FAILED)
            ->getQuery()
            ->getSingleResult();

        return [
            'total' => (int) $result['total'],
            'sent' => (int) $result['sent'],
            'failed' => (int) $result['failed'],
        ];
    }

    public function findLatestForCompany(Company $company): ?Notification
    {
        return $this->createQueryBuilder('notification')
            ->andWhere('notification.company = :company')
            ->setParameter('company', $company)
            ->orderBy('notification.createdAt', 'DESC')
            ->addOrderBy('notification.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Notification>
     */
    public function findLatestForCompanyLimited(Company $company, int $limit = 3): array
    {
        return $this->createQueryBuilder('notification')
            ->andWhere('notification.company = :company')
            ->setParameter('company', $company)
            ->orderBy('notification.createdAt', 'DESC')
            ->addOrderBy('notification.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function existsForDocumentThreshold(FleetDocument $document, string $channel, int $daysBefore, string $recipient): bool
    {
        return (int) $this->createQueryBuilder('notification')
            ->select('COUNT(notification.id)')
            ->andWhere('notification.document = :document')
            ->andWhere('notification.channel = :channel')
            ->andWhere('notification.triggerDaysBefore = :daysBefore')
            ->andWhere('notification.recipient = :recipient')
            ->setParameter('document', $document)
            ->setParameter('channel', $channel)
            ->setParameter('daysBefore', $daysBefore)
            ->setParameter('recipient', mb_strtolower(trim($recipient)))
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
