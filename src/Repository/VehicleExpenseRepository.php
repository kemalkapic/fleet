<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Vehicle;
use App\Entity\VehicleExpense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<VehicleExpense> */
class VehicleExpenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleExpense::class);
    }

    /** @return list<VehicleExpense> */
    public function findForVehicle(Vehicle $vehicle, Company $company): array
    {
        return $this->createQueryBuilder('expense')
            ->andWhere('expense.vehicle = :vehicle')
            ->andWhere('expense.company = :company')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('company', $company)
            ->orderBy('expense.expenseDate', 'DESC')
            ->addOrderBy('expense.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForVehicle(int $id, Vehicle $vehicle, Company $company): ?VehicleExpense
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
        return $this->createQueryBuilder('expense')
            ->select('vehicle.id AS vehicleId')
            ->addSelect('SUM(expense.amountBam) AS total')
            ->innerJoin('expense.vehicle', 'vehicle')
            ->andWhere('expense.company = :company')
            ->andWhere('expense.expenseDate >= :from')
            ->andWhere('expense.expenseDate < :until')
            ->andWhere('vehicle.deletedAt IS NULL')
            ->setParameter('company', $company)
            ->setParameter('from', $from)
            ->setParameter('until', $until)
            ->groupBy('vehicle.id')
            ->getQuery()
            ->getArrayResult();
    }
}
