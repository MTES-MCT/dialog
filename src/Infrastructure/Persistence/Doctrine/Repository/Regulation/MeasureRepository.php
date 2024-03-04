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

    public function findOneByUuid(string $uuid): ?Measure
    {
        return $this->createQueryBuilder('m')
            ->where('m.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
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
            ->addSelect('p', 't', 'v', 'l')
            ->innerJoin('m.regulationOrder', 'ro')
            ->innerJoin('ro.regulationOrderRecord', 'roc')
            ->leftJoin('m.locations', 'l')
            ->leftJoin('m.periods', 'p')
            ->leftJoin('p.timeSlots', 't')
            ->leftJoin('m.vehicleSet', 'v')
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getResult()
        ;
    }
}
