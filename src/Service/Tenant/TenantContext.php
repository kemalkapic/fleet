<?php

namespace App\Service\Tenant;

use App\Entity\Company;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TenantContext
{
    public function __construct(private readonly Security $security)
    {
    }

    public function getCompany(): ?Company
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return null;
        }

        return $user->getCompany();
    }

    public function requireCompany(): Company
    {
        $company = $this->getCompany();

        if (!$company instanceof Company) {
            throw new AccessDeniedHttpException('Tenant company context is required.');
        }

        return $company;
    }
}
