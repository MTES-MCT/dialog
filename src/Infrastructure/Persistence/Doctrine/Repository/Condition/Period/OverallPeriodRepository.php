<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Condition\Period;

use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Condition\Period\Repository\OverallPeriodRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class OverallPeriodRepository extends ServiceEntityRepository implements OverallPeriodRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OverallPeriod::class);
    }

    public function findOneByRegulationConditionUuid(string $uuid): ?OverallPeriod
    {
        return $this->createQueryBuilder('op')
            ->where('rc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('op.regulationCondition', 'rc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function save(OverallPeriod $overallPeriod): OverallPeriod
    {
        $this->getEntityManager()->persist($overallPeriod);

        return $overallPeriod;
    }
}
