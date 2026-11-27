<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Vehicle;
use App\Entity\VehicleFuelEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VehicleFuelEntry>
 */
class VehicleFuelEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleFuelEntry::class);
    }

    /**
     * @return list<VehicleFuelEntry>
     */
    public function findForVehicle(Vehicle $vehicle, Company $company): array
    {
        return $this->createQueryBuilder('fuel')
            ->andWhere('fuel.vehicle = :vehicle')
            ->andWhere('fuel.company = :company')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('company', $company)
            ->orderBy('fuel.entryDate', 'DESC')
            ->addOrderBy('fuel.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForVehicle(int $id, Vehicle $vehicle, Company $company): ?VehicleFuelEntry
    {
        return $this->findOneBy([
            'id' => $id,
            'vehicle' => $vehicle,
            'company' => $company,
        ]);
    }

    /** @return list<array{vehicleId: int, total: string}> */
    public function findCostTotalsByVehicle(
        Company $company,
        \DateTimeImmutable $from,
        \DateTimeImmutable $until,
    ): array {
        return $this->createQueryBuilder('fuel')
            ->select('vehicle.id AS vehicleId')
            ->addSelect('SUM(fuel.amountBam) AS total')
            ->innerJoin('fuel.vehicle', 'vehicle')
            ->andWhere('fuel.company = :company')
            ->andWhere('fuel.entryDate >= :from')
            ->andWhere('fuel.entryDate < :until')
            ->andWhere('vehicle.deletedAt IS NULL')
            ->setParameter('company', $company)
            ->setParameter('from', $from)
            ->setParameter('until', $until)
            ->groupBy('vehicle.id')
            ->getQuery()
            ->getArrayResult();
    }
}
