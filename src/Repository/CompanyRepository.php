<?php

namespace App\Repository;

use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /**
     * @return array{total: int, active: int, inactive: int}
     */
    public function getPlatformStatusCounts(): array
    {
        $result = $this->createQueryBuilder('company')
            ->select('COUNT(company.id) AS total')
            ->addSelect("SUM(CASE WHEN company.status = 'active' THEN 1 ELSE 0 END) AS active")
            ->addSelect("SUM(CASE WHEN company.status != 'active' THEN 1 ELSE 0 END) AS inactive")
            ->getQuery()
            ->getSingleResult();

        return [
            'total' => (int) $result['total'],
            'active' => (int) $result['active'],
            'inactive' => (int) $result['inactive'],
        ];
    }

    /**
     * @return list<array<string, int|string|null>>
     */
    public function findPlatformSummaries(int $limit = 6): array
    {
        $sql = <<<'SQL'
            SELECT
                company.id,
                company.name,
                company.status,
                company.vehicle_limit,
                company.driver_limit,
                company.document_limit,
                company.created_at,
                (SELECT COUNT(*) FROM vehicle WHERE vehicle.company_id = company.id AND vehicle.deleted_at IS NULL) AS vehicle_count,
                (SELECT COUNT(*) FROM driver WHERE driver.company_id = company.id AND driver.deleted_at IS NULL) AS driver_count,
                (SELECT COUNT(*) FROM fleet_document WHERE fleet_document.company_id = company.id AND fleet_document.deleted_at IS NULL) AS document_count,
                (SELECT COUNT(*) FROM app_user WHERE app_user.company_id = company.id) AS user_count
            FROM company
            ORDER BY company.created_at DESC, company.id DESC
            LIMIT :limit
            SQL;

        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            $sql,
            ['limit' => max(1, $limit)],
            ['limit' => ParameterType::INTEGER],
        )->fetchAllAssociative();

        return array_map(static function (array $row): array {
            foreach (['id', 'vehicle_limit', 'driver_limit', 'document_limit', 'vehicle_count', 'driver_count', 'document_count', 'user_count'] as $field) {
                $row[$field] = $row[$field] !== null ? (int) $row[$field] : null;
            }

            return $row;
        }, $rows);
    }
}
