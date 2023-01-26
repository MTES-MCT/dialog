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

    public function save(RegulationOrder $regulationOrder): RegulationOrder
    {
        $this->getEntityManager()->persist($regulationOrder);

        return $regulationOrder;
    }

    public function findRegulationOrdersForDatexFormat(): array
    {
        return $this->createQueryBuilder('ro')
            ->select(
                'ro.uuid',
                'ro.issuingAuthority',
                'ro.description',
                'o.startPeriod',
                'o.endPeriod',
                'loc.postalCode',
                'loc.city',
                'loc.roadName',
                'loc.fromHouseNumber',
                'ST_X(loc.fromPoint) as fromLongitude',
                'ST_Y(loc.fromPoint) as fromLatitude',
                'loc.toHouseNumber',
                'ST_X(loc.toPoint) as toLongitude',
                'ST_Y(loc.toPoint) as toLatitude',
            )
            ->innerJoin('ro.regulationCondition', 'rc')
            ->innerJoin('rc.overallPeriod', 'o')
            ->innerJoin('rc.location', 'loc')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult()
        ;
    }
}
