<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

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

    public function findRegulations(int $maxItemsPerPage, int $page, string $status): array
    {
        return $this->createQueryBuilder('roc')
            ->select('roc.uuid, o.startPeriod, o.endPeriod, roc.status, l.city, l.roadName')
            ->where('roc.status = :status')
            ->setParameter('status', $status)
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('ro.regulationCondition', 'rc')
            ->leftJoin('rc.overallPeriod', 'o')
            ->leftJoin('rc.location', 'l')
            ->orderBy('o.startPeriod', 'DESC')
            ->setFirstResult($maxItemsPerPage * ($page - 1))
            ->setMaxResults($maxItemsPerPage)
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

    public function findOneForSummary(string $uuid): array|null
    {
        return $this->createQueryBuilder('roc')
            ->select(
                'roc.uuid',
                'roc.status',
                'ro.description',
                'o.startPeriod',
                'o.endPeriod',
                'l.city',
                'l.roadName',
                'vc.maxWeight',
                'vc.maxHeight',
                'vc.maxWidth',
                'vc.maxLength',
            )
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('ro.regulationCondition', 'rc')
            ->leftJoin('rc.vehicleCharacteristics', 'vc')
            ->leftJoin('rc.overallPeriod', 'o')
            ->leftJoin('rc.location', 'l')
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
