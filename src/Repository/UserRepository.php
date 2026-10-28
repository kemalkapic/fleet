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
     * @return array{total: int, active: int, inactive: int, recentlyActive: int}
     */
    public function getPlatformStatusCounts(): array
    {
        $result = $this->createQueryBuilder('user')
            ->select('COUNT(user.id) AS total')
            ->addSelect('SUM(CASE WHEN user.isActive = true THEN 1 ELSE 0 END) AS active')
            ->addSelect('SUM(CASE WHEN user.isActive = false THEN 1 ELSE 0 END) AS inactive')
            ->getQuery()
            ->getSingleResult();

        $recentlyActive = (int) $this->createQueryBuilder('recentUser')
            ->select('COUNT(recentUser.id)')
            ->andWhere('recentUser.company IS NOT NULL')
            ->andWhere('recentUser.lastActiveAt >= :since')
            ->setParameter('since', new \DateTimeImmutable('-7 days'))
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => (int) $result['total'],
            'active' => (int) $result['active'],
            'inactive' => (int) $result['inactive'],
            'recentlyActive' => $recentlyActive,
        ];
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
