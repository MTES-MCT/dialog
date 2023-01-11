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

    public function findAll(): array
    {
        return $this->createQueryBuilder('roc')
            ->select('roc.uuid, o.startPeriod, ro.issuingAuthority, o.endPeriod')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('ro.regulationCondition', 'rc')
            ->innerJoin('rc.overallPeriod', 'o')
            ->orderBy('o.startPeriod', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult()
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
