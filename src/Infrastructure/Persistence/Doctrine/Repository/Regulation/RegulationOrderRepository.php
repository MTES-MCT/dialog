<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RegulationOrderRepository extends ServiceEntityRepository implements RegulationOrderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegulationOrder::class);
    }

    public function add(RegulationOrder $regulationOrder): RegulationOrder
    {
        $this->getEntityManager()->persist($regulationOrder);

        return $regulationOrder;
    }

    public function delete(RegulationOrder $regulationOrder): void
    {
        $this->getEntityManager()->remove($regulationOrder);
    }

    public function getDuplicateIdentifier(string $identifier): string
    {
        return $this->getEntityManager()->getConnection()->fetchOne(
            "WITH base AS (
                SELECT regexp_replace(:identifier, ' \(\d+\)$', '') AS identifier
            ),
            ref AS (
                SELECT '^' || base.identifier || ' \((\d+)\)$' AS regex
                FROM base
            ),
            numbers AS (
                SELECT (regexp_match(ro.identifier, ref.regex))[1]::int AS n
                FROM regulation_order AS ro, ref
                WHERE regexp_match(ro.identifier, ref.regex) IS NOT NULL
            ),
            numbers_with_prev AS (
                SELECT n, LAG(n) OVER (ORDER BY n) AS prev_n
                FROM numbers
                ORDER BY n
            ),
            next_duplicate AS (
                SELECT CASE
                    -- No duplicates yet
                    WHEN (SELECT COUNT(*) FROM numbers_with_prev) = 0 THEN 1
                    -- Duplicate (1) is available
                    WHEN (SELECT MIN(n) FROM numbers_with_prev) > 1 THEN 1
                    -- Default case
                    ELSE (
                        SELECT prev_n + 1
                        FROM numbers_with_prev
                        WHERE n - prev_n >= 2
                        UNION ALL
                            -- When no empty duplicate slot is available,
                            -- use last duplicate + 1
                            (SELECT MAX(n) + 1 FROM numbers_with_prev)
                        LIMIT 1
                    )
                END AS n
            )
            SELECT base.identifier || ' (' || next_duplicate.n || ')'
            FROM base, next_duplicate
            ",
            ['identifier' => $identifier],
        );
    }
}
