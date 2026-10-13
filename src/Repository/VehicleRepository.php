<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vehicle>
 */
class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    /**
     * @return list<Vehicle>
     */
    public function findForCompany(Company $company): array
    {
        return $this->findBy([
            'company' => $company,
            'deletedAt' => null,
        ], ['registrationNumber' => 'ASC']);
    }

    public function countForCompany(Company $company): int
    {
        return (int) $this->createQueryBuilder('vehicle')
            ->select('COUNT(vehicle.id)')
            ->andWhere('vehicle.company = :company')
            ->andWhere('vehicle.deletedAt IS NULL')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneForCompany(int $id, Company $company): ?Vehicle
    {
        return $this->findOneBy([
            'id' => $id,
            'company' => $company,
            'deletedAt' => null,
        ]);
    }

    public function existsActiveRegistration(Company $company, string $registrationNumber, ?Vehicle $exceptVehicle = null): bool
    {
        $builder = $this->createQueryBuilder('vehicle')
            ->select('COUNT(vehicle.id)')
            ->andWhere('vehicle.company = :company')
            ->andWhere('vehicle.registrationNumber = :registrationNumber')
            ->andWhere('vehicle.deletedAt IS NULL')
            ->setParameter('company', $company)
            ->setParameter('registrationNumber', mb_strtoupper(trim($registrationNumber)));

        if ($exceptVehicle instanceof Vehicle && $exceptVehicle->getId() !== null) {
            $builder
                ->andWhere('vehicle.id != :vehicleId')
                ->setParameter('vehicleId', $exceptVehicle->getId());
        }

        return (int) $builder->getQuery()->getSingleScalarResult() > 0;
    }
}
