<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Pagination;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RegulationOrderRecordRepository extends ServiceEntityRepository implements RegulationOrderRecordRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegulationOrderRecord::class);
    }

    public function findRegulations(int $page, string $status): array
    {
        return $this->createQueryBuilder('roc')
            ->select('roc.uuid, o.startPeriod, o.endPeriod, roc.status')
            ->where('roc.status = :status')
            ->setParameter('status', $status)
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('ro.regulationCondition', 'rc')
            ->leftJoin('rc.overallPeriod', 'o')
            ->orderBy('o.startPeriod', 'DESC')
            ->setFirstResult(Pagination::MAX_ITEMS_PER_PAGE * ($page - 1))
            ->setMaxResults(Pagination::MAX_ITEMS_PER_PAGE)
            ->getQuery()
            ->getResult()
        ;
    }

    public function countRegulations(string $status): int
    {
        return $this->createQueryBuilder('roc')
            ->select('count(roc.uuid)')
            ->where('roc.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findOneByUuid(string $uuid): RegulationOrderRecord|null
    {
        return $this->createQueryBuilder('roc')
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('ro.regulationCondition', 'rc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function save(RegulationOrderRecord $regulationOrderRecord): RegulationOrderRecord
    {
        $this->getEntityManager()->persist($regulationOrderRecord);

        return $regulationOrderRecord;
    }
}
