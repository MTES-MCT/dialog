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

    public function findRegulationOrders(): array
    {
        return $this->createQueryBuilder('ro')
            ->select('ro.uuid, o.startPeriod, ro.issuingAuthority, o.endPeriod')
            ->innerJoin('ro.regulationCondition', 'rc')
            ->innerJoin('rc.overallPeriod', 'o')
            ->orderBy('o.startPeriod', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findRegulationOrdersForDatexFormat(): array
    {
        return $this->createQueryBuilder('ro')
            ->select('ro.uuid, ro.description, ro.issuingAuthority, o.startPeriod, o.endPeriod')
            ->innerJoin('ro.regulationCondition', 'rc')
            ->innerJoin('rc.overallPeriod', 'o')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult()
        ;
    }
}
