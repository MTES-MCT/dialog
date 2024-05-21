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

    // TODO : test this if ST_Envelope is not a Polygon
    // TODO : check if the query result is empty or malformed ?
    public function findAllLocationsBbox(): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('longitude_x', 'longitude_x')
            ->addScalarResult('latitude_y', 'latitude_y')
        ;
        $bbox_points = $this->getEntityManager()
                     ->createNativeQuery('
WITH bbox_as_points AS (
SELECT ST_DumpPoints(ST_Envelope(ST_Extent(location.geometry))) AS dump_points FROM location
)
SELECT ST_X((bbox_as_points.dump_points).geom) AS longitude_x, ST_Y((bbox_as_points.dump_points).geom) AS latitude_y
FROM bbox_as_points
WHERE (bbox_as_points.dump_points).path IN (ARRAY[1,1], ARRAY[1,3])
',
                         $rsm,
                     )
                     ->getResult()
        ;

        return [
            [$bbox_points[0]['longitude_x'], $bbox_points[0]['latitude_y']],
            [$bbox_points[1]['longitude_x'], $bbox_points[1]['latitude_y']],
        ];
    }

    public function findFilteredLocationsAsGeoJson(string $permanentAndOrTemporaryFilter, string $draftFilter): array
    {
        $rsm = new ResultSetMapping();
        $geoJSONs = $this->getEntityManager()
                  ->createNativeQuery('
WITH filtered_location AS (
SELECT (regulation_order.end_date IS NULL) AS is_permanent, (regulation_order_record.status = \'draft\') AS is_draft,
       location.geometry AS geometry, location.uuid AS location_uuid
FROM location
JOIN measure ON measure.uuid = location.measure_uuid
JOIN regulation_order ON regulation_order.uuid = measure.regulation_order_uuid
JOIN regulation_order_record ON regulation_order_record.regulation_order_uuid = regulation_order.uuid
)
SELECT ST_AsGeoJSON(filtered_location.*) AS geo_json
FROM filtered_location
WHERE
((:with_published_only AND NOT filtered_location.is_draft) OR :with_drafts_and_published)
AND
((:with_permanents_only AND is_permanent) OR (:with_temporaries_only AND NOT is_permanent) OR (:with_temporaries_and_permanents))
',
                      $rsm,
                  )
                  ->setParameters([
                      'with_published_only' => ($draftFilter != 'yes'),
                      'with_drafts_and_published' => ($draftFilter == 'yes'),
                      'with_permanents_only' => ($permanentAndOrTemporaryFilter == 'permanents_only'),
                      'with_temporaries_only' => ($permanentAndOrTemporaryFilter == 'temporaries_only'),
                      'with_temporaries_and_permanents' => ($permanentAndOrTemporaryFilter == 'permanents_and_temporaries'),
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
