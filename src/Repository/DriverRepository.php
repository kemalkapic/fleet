<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Driver;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Driver>
 */
class DriverRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Driver::class);
    }

    /**
     * @return list<Driver>
     */
    public function findForCompany(Company $company): array
    {
        return $this->findBy([
            'company' => $company,
            'deletedAt' => null,
        ], ['lastName' => 'ASC', 'firstName' => 'ASC']);
    }

    public function countForCompany(Company $company): int
    {
        return (int) $this->createQueryBuilder('driver')
            ->select('COUNT(driver.id)')
            ->andWhere('driver.company = :company')
            ->andWhere('driver.deletedAt IS NULL')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{total: int, active: int, inactive: int}
     */
    public function getPlatformStatusCounts(): array
    {
        $result = $this->createQueryBuilder('driver')
            ->select('COUNT(driver.id) AS total')
            ->addSelect("SUM(CASE WHEN driver.status = 'active' THEN 1 ELSE 0 END) AS active")
            ->addSelect("SUM(CASE WHEN driver.status != 'active' THEN 1 ELSE 0 END) AS inactive")
            ->andWhere('driver.deletedAt IS NULL')
            ->getQuery()
            ->getSingleResult();

        return [
            'total' => (int) $result['total'],
            'active' => (int) $result['active'],
            'inactive' => (int) $result['inactive'],
        ];
    }

    public function findOneForCompany(int $id, Company $company): ?Driver
    {
        return $this->findOneBy([
            'id' => $id,
            'company' => $company,
            'deletedAt' => null,
        ]);
    }
}
