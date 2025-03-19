<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\User\Organization;
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

    public function getDuplicateIdentifier(string $identifier, Organization $organization): string
    {
        // Algorithm for choosing the duplicate's identifier must avoid identifier collisions with the original or existing duplicates
        $nextNumber = $this->getEntityManager()->getConnection()->fetchOne(
            "WITH regex AS (
                SELECT '^' || :identifier || '-(\d+)$' AS pattern
            ),
            duplicates AS (
                SELECT (regexp_match(ro.identifier, re.pattern))[1]::int AS number
                FROM regulation_order AS ro
                INNER JOIN regulation_order_record AS roc ON roc.regulation_order_uuid = ro.uuid
                INNER JOIN regex AS re ON true
                WHERE regexp_match(ro.identifier, re.pattern) IS NOT NULL
                AND roc.organization_uuid = :organization_uuid
            )
            SELECT MAX(d.number) + 1
            FROM duplicates AS d
            ",
            ['identifier' => $identifier, 'organization_uuid' => $organization->getUuid()],
        );

        $nextNumber = $nextNumber ? (int) $nextNumber : 1;

        return \sprintf('%s-%d', $identifier, $nextNumber);
    }

    public function findVisasAndReasonsByRegulationOrderUuid(string $uuid): array
    {
        return $this->createQueryBuilder('ro')
            ->select('visaModel.visas, ro.additionalVisas, ro.additionalReasons')
            ->leftJoin('ro.visaModel', 'visaModel')
            ->where('ro.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function countRegulationOrdersForOrganizationDuringCurrentMonth(string $organizationUuid): int
    {
        return $this->createQueryBuilder('roc')
           ->select('COUNT(r) + 1 AS number_of_records')
           ->where('roc.organization_uuid = :uuid')
           ->andWhere('MONTH(roc.created_at) = MONTH(CURRENT_DATE())')
           ->andWhere('YEAR(roc.created_at) = YEAR(CURRENT_DATE())')
           ->getQuery()
           ->getSingleScalarResult()
        ;
    }
}
