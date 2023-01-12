<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Condition;

use App\Domain\Condition\Location;
use App\Domain\Condition\Repository\LocationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class LocationRepository extends ServiceEntityRepository implements LocationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    public function save(Location $location): Location
    {
        $this->getEntityManager()->persist($location);

        return $location;
    }

    public function findOneByRegulationConditionUuid(string $uuid): ?Location
    {
        return $this->createQueryBuilder('loc')
            ->where('rc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('loc.regulationCondition', 'rc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
