<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Vehicle;
use App\Entity\VehicleServiceRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<VehicleServiceRecord> */
class VehicleServiceRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleServiceRecord::class);
    }

    /** @return list<VehicleServiceRecord> */
    public function findForVehicle(Vehicle $vehicle, Company $company): array
    {
        return $this->createQueryBuilder('service')
            ->addSelect('attachments')
            ->leftJoin('service.attachments', 'attachments')
            ->andWhere('service.vehicle = :vehicle')
            ->andWhere('service.company = :company')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('company', $company)
            ->orderBy('service.serviceDate', 'DESC')
            ->addOrderBy('service.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForVehicle(int $id, Vehicle $vehicle, Company $company): ?VehicleServiceRecord
    {
        return $this->createQueryBuilder('service')
            ->addSelect('attachments')
            ->leftJoin('service.attachments', 'attachments')
            ->andWhere('service.id = :id')
            ->andWhere('service.vehicle = :vehicle')
            ->andWhere('service.company = :company')
            ->setParameter('id', $id)
            ->setParameter('vehicle', $vehicle)
            ->setParameter('company', $company)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<array{vehicleId: int, total: string}> */
    public function findCostTotalsByVehicle(
        Company $company,
        \DateTimeImmutable $from,
        \DateTimeImmutable $until,
    ): array {
        return $this->createQueryBuilder('service')
            ->select('vehicle.id AS vehicleId')
            ->addSelect('SUM(service.costBam) AS total')
            ->innerJoin('service.vehicle', 'vehicle')
            ->andWhere('service.company = :company')
            ->andWhere('service.serviceDate >= :from')
            ->andWhere('service.serviceDate < :until')
            ->andWhere('service.costBam IS NOT NULL')
            ->andWhere('vehicle.deletedAt IS NULL')
            ->setParameter('company', $company)
            ->setParameter('from', $from)
            ->setParameter('until', $until)
            ->groupBy('vehicle.id')
            ->getQuery()
            ->getArrayResult();
    }
}
