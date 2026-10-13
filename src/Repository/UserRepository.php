<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => mb_strtolower(trim($email))]);
    }

    /**
     * @return list<User>
     */
    public function findActiveCompanyAdmins(Company $company): array
    {
        $users = $this->findBy([
            'company' => $company,
            'isActive' => true,
        ], ['email' => 'ASC']);

        return array_values(array_filter(
            $users,
            static fn (User $user): bool => in_array('ROLE_COMPANY_ADMIN', $user->getAssignedRoles(), true),
        ));
    }
}
