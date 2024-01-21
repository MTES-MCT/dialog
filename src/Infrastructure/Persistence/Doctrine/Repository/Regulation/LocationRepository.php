<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class LocationRepository extends ServiceEntityRepository implements LocationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    public function add(Location $location): Location
    {
        $this->getEntityManager()->persist($location);

        return $location;
    }

    public function delete(Location $location): void
    {
        $this->getEntityManager()->remove($location);
    }

    public function findOneByUuid(string $uuid): ?Location
    {
        return $this->createQueryBuilder('loc')
            ->where('loc.uuid = :uuid')
            ->innerJoin('loc.regulationOrder', 'ro')
            ->leftJoin('loc.measures', 'measure')
            ->leftJoin('measure.periods', 'periods')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findGeoJsonGeometriesByRegulationOrderUuid(string $uuid): array
    {
        $geometryStrings = $this->createQueryBuilder('loc')
            ->select(
                'ST_AsGeoJSON(loc.geometry)',
            )
            ->where(
                'loc.geometry IS NOT NULL',
                'loc.regulationOrder = :uuid',
            )
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getSingleColumnResult();

        $geometries = [];

        foreach ($geometryStrings as $str) {
            $geometries[] = json_decode($str, associative: true, flags: JSON_THROW_ON_ERROR);
        }

        return $geometries;
    }
}
