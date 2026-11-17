<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\VehicleServiceAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<VehicleServiceAttachment> */
class VehicleServiceAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleServiceAttachment::class);
    }

    public function findOneForCompany(int $id, Company $company): ?VehicleServiceAttachment
    {
        return $this->createQueryBuilder('attachment')
            ->innerJoin('attachment.serviceRecord', 'service')
            ->andWhere('attachment.id = :id')
            ->andWhere('service.company = :company')
            ->setParameter('id', $id)
            ->setParameter('company', $company)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
