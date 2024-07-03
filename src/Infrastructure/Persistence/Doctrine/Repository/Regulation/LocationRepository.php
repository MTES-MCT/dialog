<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Application\DateUtilsInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class LocationRepository extends ServiceEntityRepository implements LocationRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private DateUtilsInterface $dateUtils,
    ) {
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
            ->addSelect('m')
            ->where('l.uuid = :uuid')
            ->innerJoin('l.measure', 'm')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAllForMapAsGeoJSON(
        bool $includePermanentRegulations = false,
        bool $includeTemporaryRegulations = false,
        bool $includeUpcomingRegulations = false,
        bool $includePastRegulations = false,
        float $toleranceInMeters = 10,
        ?array $bounds = null,
    ): string {
        $includeNone = !$includePermanentRegulations && !$includeTemporaryRegulations;
        $permanentOnly = $includePermanentRegulations && !$includeTemporaryRegulations;
        $temporaryOnly = !$includePermanentRegulations && $includeTemporaryRegulations;

        if ($includeNone) {
            return json_encode([
                'type' => 'FeatureCollection',
                'features' => [],
            ]); // we return no regulations
        }

        $regulationTypeWhereClause =
            $permanentOnly
            ? 'AND ro.end_date IS NULL'
            : ($temporaryOnly
                ? 'AND ro.end_date IS NOT NULL'
                : '');

        $upcomingOnly = $includeUpcomingRegulations && !$includePastRegulations;
        $pastOnly = !$includeUpcomingRegulations && $includePastRegulations;
        $presentOnly = !$includeUpcomingRegulations && !$includePastRegulations;

        $regulationDatesWhereClause =
            $upcomingOnly
            ? 'AND (((ro.end_date >= :now OR ro.end_date IS NULL) AND ro.start_date <= :now) OR ro.start_date > :now)'
            : ($pastOnly
                ? 'AND (((ro.end_date >= :now OR ro.end_date IS NULL) AND ro.start_date <= :now) OR ro.end_date < :now)'
                : ($presentOnly
                    ? 'AND ((ro.end_date >= :now OR ro.end_date IS NULL) AND ro.start_date <= :now)'
                    : ''));

        $geometryField = 'l.geometry';
        $boundsWhereClause = '';

        if ($bounds) {
            $geometryField = 'ST_Intersection(l.geometry, ST_MakeEnvelope(:xMin, :yMin, :xMax, :yMax, 4326))';
            $boundsWhereClause = 'AND (l.geometry && ST_MakeEnvelope(:xMin, :yMin, :xMax, :yMax, 4326))';
        }

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            sprintf(
                'SELECT ST_AsGeoJSON(
                    ST_ReducePrecision(
                        ST_Simplify(
                            %s,
                            -- ST_Simplify tolerance must be in degrees (EPSG:4326), convert from meters using 0.00001° ~= 1m
                            0.00001 * :toleranceInMeters
                        ),
                        0.00001 -- 6 decimals max (~1m precision) to limit transfer size
                    )
                ) AS geometry, m.type AS measure_type, l.uuid AS location_uuid
                FROM location AS l
                INNER JOIN measure AS m ON m.uuid = l.measure_uuid
                INNER JOIN regulation_order AS ro ON ro.uuid = m.regulation_order_uuid
                INNER JOIN regulation_order_record AS roc ON ro.uuid = roc.regulation_order_uuid
                WHERE roc.status = :status
                AND l.geometry IS NOT NULL
                %s
                %s
                %s
                ',
                $geometryField,
                $regulationTypeWhereClause,
                $regulationDatesWhereClause,
                $boundsWhereClause,
            ),
            [
                'toleranceInMeters' => $toleranceInMeters,
                'status' => RegulationOrderRecordStatusEnum::PUBLISHED,
                'now' => $this->dateUtils->getNow()->format('Y-m-d'),
                ...($bounds ? [
                    'xMin' => $bounds[0],
                    'yMin' => $bounds[1],
                    'xMax' => $bounds[2],
                    'yMax' => $bounds[3],
                ] : []),
            ],
        );

        $features = [];

        foreach ($rows as $row) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['geometry']),
                'properties' => [
                    'location_uuid' => $row['location_uuid'],
                    'measure_type' => $row['measure_type'],
                ],
            ];
        }

        return json_encode([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}
