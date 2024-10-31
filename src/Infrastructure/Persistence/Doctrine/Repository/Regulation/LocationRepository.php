<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
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
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
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

        $parameters = [
            'status' => RegulationOrderRecordStatusEnum::PUBLISHED->value,
            'measureTypes' => $measureTypes,
        ];

        $regulationTypeWhereClause = '';

        if ($permanentOnly) {
            $regulationTypeWhereClause = 'AND ro.category = :permanentCategory';
            $parameters['permanentCategory'] = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        } elseif ($temporaryOnly) {
            $regulationTypeWhereClause = 'AND ro.category <> :permanentCategory';
            $parameters['permanentCategory'] = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        }

        $types = [
            'measureTypes' => ArrayParameterType::STRING,
        ];

        $measureDatesCondition = '';

        if ($startDate || $endDate) {
            if ($startDate && $endDate && $startDate > $endDate) {
                // Renvoie un résultat vide pour éviter une erreur dans le cas où la date de fin est avant la date de début
                $measureDatesCondition = 'AND FALSE';
            } else {
                // Principe : on garde une localisation si l'intervalle défini par les dates des périodes
                // intersecte au moins partiellement l'intervalle défini par les filtres.
                // En PostgreSQL, daterange permet de représenter un intervalle de date.
                // https://www.postgresql.org/docs/13/rangetypes.html
                // NB : si l'arrêté n'a pas encore de période, EXISTS renverra FALSE, donc on ne retiendra pas ses localisations, comme attendu.
                $measureDatesCondition = 'AND EXISTS (
                    SELECT 1
                    FROM period AS p
                    WHERE p.measure_uuid = m.uuid 
                    AND daterange((:startDate)::date, (:endDate)::date, \'[]\') && daterange(p.start_datetime::date, p.end_datetime::date)
                )';

                $parameters['startDate'] = $startDate?->format(\DateTimeInterface::ATOM);
                $parameters['endDate'] = $endDate?->format(\DateTimeInterface::ATOM);
            }
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
                %s
                ',
                $regulationTypeWhereClause,
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
