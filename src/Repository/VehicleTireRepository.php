<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Vehicle;
use App\Entity\VehicleTire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VehicleTire>
 */
class VehicleTireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleTire::class);
    }

    /**
     * @return list<VehicleTire>
     */
    public function findForVehicle(Vehicle $vehicle, Company $company): array
    {
        return $this->createQueryBuilder('tire')
            ->andWhere('tire.vehicle = :vehicle')
            ->andWhere('tire.company = :company')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('company', $company)
            ->orderBy('tire.sortOrder', 'ASC')
            ->addOrderBy('tire.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
