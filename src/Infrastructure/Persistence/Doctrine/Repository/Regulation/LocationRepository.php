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
        bool $includePermanentRegulations = false,
        bool $includeTemporaryRegulations = false,
        array $measureTypes = [],
    ): string {
        $includeNone = !$includePermanentRegulations && !$includeTemporaryRegulations && empty($measureTypes);
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
        /* $regulationTypeCondition = $measureTypes ? 'AND m.type IN (:measureTypes)' : ''; */

        $parameters = ['status' => RegulationOrderRecordStatusEnum::PUBLISHED->value];
        $regulationTypeCondition = '';
        $types = [];

        if ($measureTypes) {
            $regulationTypeCondition = 'AND m.type IN (:measureTypes)';
            $parameters['measureTypes'] = $measureTypes;
            $types = ['measureTypes' => ArrayParameterType::STRING];
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
                %s
                %s
                ',
                $regulationTypeWhereClause,
                $regulationTypeCondition,
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
