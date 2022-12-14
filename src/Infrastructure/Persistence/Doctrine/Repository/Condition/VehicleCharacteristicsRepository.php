<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Condition;

use App\Domain\Condition\Repository\VehicleCharacteristicsRepositoryInterface;
use App\Domain\Condition\VehicleCharacteristics;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class VehicleCharacteristicsRepository extends ServiceEntityRepository implements VehicleCharacteristicsRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleCharacteristics::class);
    }

    public function findOneByRegulationConditionUuid(string $uuid): ?VehicleCharacteristics
    {
        return $this->createQueryBuilder('vc')
            ->where('rc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('vc.regulationCondition', 'rc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function save(VehicleCharacteristics $vehicleCharacteristics): VehicleCharacteristics
    {
        $this->getEntityManager()->persist($vehicleCharacteristics);

        return $vehicleCharacteristics;
    }
}
