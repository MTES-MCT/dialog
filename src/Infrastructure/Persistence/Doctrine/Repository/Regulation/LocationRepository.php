<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
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
        return $this->createQueryBuilder('l')
            ->where('l.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findFilteredLocationsAsGeoJson(string $permanentAndOrTemporaryFilter, string $futureFilter): array
    {
        // we want to retrieve only LineString : location.geometry can be LineString or MultiLineString, so we apply ST_Multi to have only MultiLineString, then we apply ST_Dump to have only LineString
        $rsm = new ResultSetMapping();
        $geoJSONs = $this->getEntityManager()
                  ->createNativeQuery('
WITH location_alias AS (
SELECT (regulation_order.end_date IS NULL) AS is_permanent, (regulation_order_record.status = \'draft\') AS is_draft,
       measure.type AS measure_type, regulation_order.start_date AS regulation_start_date, regulation_order.end_date AS regulation_end_date, 
       (ST_Dump(ST_Multi(location.geometry))).geom AS geometry, location.uuid AS location_uuid
FROM location
JOIN measure ON measure.uuid = location.measure_uuid
JOIN regulation_order ON regulation_order.uuid = measure.regulation_order_uuid
JOIN regulation_order_record ON regulation_order_record.regulation_order_uuid = regulation_order.uuid
),
filtered_location AS (
SELECT is_permanent, is_draft, measure_type, 
       geometry, location_uuid,
       ST_StartPoint(geometry) AS geometry_start_point,
       ST_EndPoint(geometry) AS geometry_end_point
FROM location_alias
WHERE
(NOT is_draft)
AND
measure_type IN (\'noEntry\', \'speedLimitation\')
AND
(is_permanent OR (regulation_end_date >= NOW()))
AND
(:with_current_and_future_regulation OR (:with_current_regulation_only AND (regulation_start_date <= NOW())))
AND
((:with_permanents_only AND is_permanent) OR (:with_temporaries_only AND NOT is_permanent) OR (:with_temporaries_and_permanents))
)
SELECT ST_AsGeoJSON(filtered_location.*) AS geo_json
FROM filtered_location
',
                      $rsm,
                  )
                  ->setParameters([
                      'with_permanents_only' => ($permanentAndOrTemporaryFilter == 'permanents_only'),
                      'with_temporaries_only' => ($permanentAndOrTemporaryFilter == 'temporaries_only'),
                      'with_temporaries_and_permanents' => ($permanentAndOrTemporaryFilter == 'permanents_and_temporaries'),
                      'with_current_and_future_regulation' => ($futureFilter == 'yes'),
                      'with_current_regulation_only' => ($futureFilter != 'yes'),
                  ])
                  ->getSingleColumnResult()
        ;
        $geometries = [];
        foreach ($geoJSONs as $geoJSON) {
            $geometries[] = json_decode($geoJSON, associative: true, flags: JSON_THROW_ON_ERROR);
        }

        return $geometries;
    }
}
