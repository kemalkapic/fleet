<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Driver;
use App\Entity\FleetDocument;
use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FleetDocument>
 */
class FleetDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FleetDocument::class);
    }

    /**
     * @return list<FleetDocument>
     */
    public function findForCompany(Company $company): array
    {
        return $this->createQueryBuilder('document')
            ->andWhere('document.company = :company')
            ->andWhere('document.deletedAt IS NULL')
            ->setParameter('company', $company)
            ->orderBy('document.validUntil', 'ASC')
            ->addOrderBy('document.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countForCompany(Company $company): int
    {
        return (int) $this->createQueryBuilder('document')
            ->select('COUNT(document.id)')
            ->andWhere('document.company = :company')
            ->andWhere('document.deletedAt IS NULL')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countExpiredForCompany(Company $company, \DateTimeImmutable $today): int
    {
        return (int) $this->createQueryBuilder('document')
            ->select('COUNT(document.id)')
            ->andWhere('document.company = :company')
            ->andWhere('document.deletedAt IS NULL')
            ->andWhere('document.validUntil < :today')
            ->setParameter('company', $company)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countExpiringWithinForCompany(Company $company, \DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return (int) $this->createQueryBuilder('document')
            ->select('COUNT(document.id)')
            ->andWhere('document.company = :company')
            ->andWhere('document.deletedAt IS NULL')
            ->andWhere('document.validUntil BETWEEN :from AND :to')
            ->setParameter('company', $company)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<FleetDocument>
     */
    public function findExpiringBetweenForCompany(
        Company $company,
        \DateTimeImmutable $from,
        \DateTimeImmutable $toExclusive,
    ): array {
        return $this->createQueryBuilder('document')
            ->andWhere('document.company = :company')
            ->andWhere('document.deletedAt IS NULL')
            ->andWhere('document.validUntil >= :from')
            ->andWhere('document.validUntil < :to')
            ->setParameter('company', $company)
            ->setParameter('from', $from)
            ->setParameter('to', $toExclusive)
            ->orderBy('document.validUntil', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<FleetDocument>
     */
    public function findUpcomingForCompany(Company $company, \DateTimeImmutable $today, int $limit = 10): array
    {
        return $this->createQueryBuilder('document')
            ->andWhere('document.company = :company')
            ->andWhere('document.deletedAt IS NULL')
            ->andWhere('document.validUntil >= :today')
            ->setParameter('company', $company)
            ->setParameter('today', $today)
            ->orderBy('document.validUntil', 'ASC')
            ->addOrderBy('document.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<FleetDocument>
     */
    public function findExpiredForCompany(Company $company, \DateTimeImmutable $today, int $limit = 10): array
    {
        return $this->createQueryBuilder('document')
            ->andWhere('document.company = :company')
            ->andWhere('document.deletedAt IS NULL')
            ->andWhere('document.validUntil < :today')
            ->setParameter('company', $company)
            ->setParameter('today', $today)
            ->orderBy('document.validUntil', 'ASC')
            ->addOrderBy('document.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneForCompany(int $id, Company $company): ?FleetDocument
    {
        return $this->createQueryBuilder('document')
            ->andWhere('document.id = :id')
            ->andWhere('document.company = :company')
            ->andWhere('document.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->setParameter('company', $company)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<FleetDocument>
     */
    public function findForVehicle(Vehicle $vehicle, Company $company): array
    {
        return $this->createQueryBuilder('document')
            ->andWhere('document.vehicle = :vehicle')
            ->andWhere('document.company = :company')
            ->andWhere('document.deletedAt IS NULL')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('company', $company)
            ->orderBy('document.validUntil', 'ASC')
            ->addOrderBy('document.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForVehicleByType(Vehicle $vehicle, Company $company, string $type): ?FleetDocument
    {
        return $this->createQueryBuilder('document')
            ->andWhere('document.vehicle = :vehicle')
            ->andWhere('document.company = :company')
            ->andWhere('document.type = :type')
            ->andWhere('document.deletedAt IS NULL')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('company', $company)
            ->setParameter('type', $type)
            ->orderBy('document.validUntil', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<FleetDocument>
     */
    public function findForDriver(Driver $driver, Company $company): array
    {
        return $this->createQueryBuilder('document')
            ->andWhere('document.driver = :driver')
            ->andWhere('document.company = :company')
            ->andWhere('document.deletedAt IS NULL')
            ->setParameter('driver', $driver)
            ->setParameter('company', $company)
            ->orderBy('document.validUntil', 'ASC')
            ->addOrderBy('document.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<FleetDocument>
     */
    public function findExpiringOn(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('document')
            ->andWhere('document.deletedAt IS NULL')
            ->andWhere('document.validUntil = :date')
            ->setParameter('date', $date)
            ->orderBy('document.validUntil', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
