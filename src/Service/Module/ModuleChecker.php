<?php

namespace App\Service\Module;

use App\Entity\Company;
use App\Enum\Module;
use App\Repository\CompanyModuleRepository;

class ModuleChecker
{
    public function __construct(private readonly CompanyModuleRepository $companyModules)
    {
    }

    public function isEnabled(Company $company, Module $module): bool
    {
        return $this->companyModules->isEnabled($company, $module);
    }
}
