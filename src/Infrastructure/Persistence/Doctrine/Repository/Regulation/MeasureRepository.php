<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MeasureRepository extends ServiceEntityRepository implements MeasureRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Measure::class);
    }

    public function add(Measure $measure): Measure
    {
        $this->getEntityManager()->persist($measure);

        return $measure;
    }

    public function delete(Measure $measure): void
    {
        $this->getEntityManager()->remove($measure);
    }

    public function findByRegulationOrderRecordUuid(string $uuid): array
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.location', 'l')
            ->innerJoin('l.regulationOrder', 'ro')
            ->innerJoin('ro.regulationOrderRecord', 'roc')
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getResult()
        ;
    }
}
