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
}
