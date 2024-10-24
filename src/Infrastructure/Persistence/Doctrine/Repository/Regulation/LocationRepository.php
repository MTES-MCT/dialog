<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

final class LocationRepository extends ServiceEntityRepository implements LocationRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
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
        array $measureTypes = [],
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
    ): string {
        $includeNone = empty($measureTypes);

        if ($includeNone) {
            return json_encode([
                'type' => 'FeatureCollection',
                'features' => [],
            ]); // we return no regulations
        }

        $parameters = [
            'status' => RegulationOrderRecordStatusEnum::PUBLISHED->value,
            'measureTypes' => $measureTypes,
        ];
        $types = [
            'measureTypes' => ArrayParameterType::STRING,
        ];

        $measureDatesCondition = '';

        if ($startDate || $endDate) {
            $measureDatesCondition = 'AND CASE 
                WHEN EXISTS (--On verifie que la mesure possède une plage 
                    SELECT 1
                    FROM period AS p
                    WHERE p.measure_uuid = m.uuid 
                )
                THEN EXISTS (
                    SELECT 1
                    FROM period AS p
                    WHERE p.measure_uuid = m.uuid 
                    AND CASE
                        WHEN (:startDate)::date IS NOT NULL AND (:endDate)::date IS NOT NULL
                            THEN p.start_datetime < (:endDate)::date AND p.end_datetime > (:startDate)::date
                        WHEN (:startDate)::date IS NOT NULL
                            THEN p.end_datetime > (:startDate)::date
                        ELSE -- c\'est-à-dire (:endDate)::date IS NOT NULL
                            p.start_datetime > (:endDate)::date
                    END
                )
                ELSE ( --La mesure ne possède pas de plage on compare les dates du filtre avec les dates de l arrêté 
                    CASE
                    WHEN (:startDate)::date IS NOT NULL AND (:endDate)::date IS NOT NULL
                        THEN ro.start_date < (:endDate)::date AND (ro.end_date IS NULL OR ro.end_date > (:startDate)::date)
                    WHEN (:startDate)::date IS NOT NULL
                        THEN ro.end_date IS NULL OR ro.end_date > (:startDate)::date
                    ELSE -- c\'est-à-dire (:endDate)::date IS NOT NULL
                        ro.start_date < (:endDate)::date
                    END
                )
                END';
            $parameters['startDate'] = $startDate?->format(\DateTimeInterface::ATOM);
            $parameters['endDate'] = $endDate?->format(\DateTimeInterface::ATOM);
        }

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            \sprintf(
                'SELECT ST_AsGeoJSON(
                    ST_SimplifyPreserveTopology(
                        l.geometry,
                        -- Simplify lines smaller than 3m (0.00001° ~= 1m) to reduce transfer size
                        3 * 0.00001
                    )
                ) AS geometry, m.type AS measure_type, l.uuid AS location_uuid
                FROM location AS l
                INNER JOIN measure AS m ON m.uuid = l.measure_uuid
                INNER JOIN regulation_order AS ro ON ro.uuid = m.regulation_order_uuid
                INNER JOIN regulation_order_record AS roc ON ro.uuid = roc.regulation_order_uuid
                WHERE roc.status = :status
                AND l.geometry IS NOT NULL
                AND m.type IN (:measureTypes)
                %s
                ',
                $measureDatesCondition,
            ),
            $parameters,
            $types,
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
