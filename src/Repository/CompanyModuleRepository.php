<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\CompanyModule;
use App\Enum\Module;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanyModule>
 */
class CompanyModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyModule::class);
    }

    /**
     * @return array<string, CompanyModule>
     */
    public function findForCompanyIndexed(Company $company): array
    {
        $items = $this->findBy(['company' => $company]);
        $indexed = [];

        foreach ($items as $item) {
            $indexed[$item->getModuleKey()] = $item;
        }

        return $indexed;
    }

    public function isEnabled(Company $company, Module $module): bool
    {
        $companyModule = $this->findOneBy([
            'company' => $company,
            'moduleKey' => $module->value,
        ]);

        return $companyModule instanceof CompanyModule && $companyModule->isEnabled();
    }
}
