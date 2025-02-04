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
            ->addSelect('m', 'v', 'p', 'd', 't', 'sa')
            ->innerJoin('l.measure', 'm')
            ->innerjoin('m.periods', 'p')
            ->leftJoin('m.vehicleSet', 'v')
            ->leftJoin('p.dailyRange', 'd')
            ->leftJoin('p.timeSlots', 't')
            ->leftJoin('l.storageArea', 'sa')
            ->where('l.uuid = :uuid')
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
        $parameters = [
            'status' => RegulationOrderRecordStatusEnum::PUBLISHED->value,
            'measureTypes' => $measureTypes,
        ];

        $types = [
            'measureTypes' => ArrayParameterType::STRING,
        ];

        if ($includePermanentRegulations && $includeTemporaryRegulations) {
            $regulationTypeWhereClause = '';
        } elseif ($includePermanentRegulations) {
            $regulationTypeWhereClause = 'AND ro.category = :permanentCategory';
            $parameters['permanentCategory'] = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        } elseif ($includeTemporaryRegulations) {
            $regulationTypeWhereClause = 'AND ro.category <> :permanentCategory';
            $parameters['permanentCategory'] = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        } else {
            $regulationTypeWhereClause = 'AND FALSE';
        }

        $measureDatesCondition = '';

        if ($startDate || $endDate) {
            if ($startDate && $endDate && $startDate > $endDate) {
                // Renvoie un résultat vide pour éviter une erreur dans le cas où la date de fin est avant la date de début
                $measureDatesCondition = 'AND FALSE';
            } else {
                // Principe : on garde une localisation si l'intervalle défini par ses périodes
                // intersecte au moins partiellement l'intervalle défini par les filtres.
                // En PostgreSQL, tsrange permet de représenter un intervalle de date et heure.
                // https://www.postgresql.org/docs/13/rangetypes.html
                // NB : si l'arrêté n'a pas encore de période, EXISTS renverra FALSE, donc on ne retiendra pas ses localisations, comme attendu.
                $measureDatesCondition = 'AND EXISTS (
                    SELECT 1
                    FROM period AS p
                    WHERE p.measure_uuid = m.uuid
                    -- ATTENTION : startDate et endDate sont données comme "inclus" toutes les deux,
                    -- et elles ont une heure qui vaut 00h00.
                    -- Donc dans le cas où startDate et endDate désignent toutes les deux le 12/01/2025 par exemple,
                    -- cela correspondrait à un intervalle de temps vide, et on ne sélectionnerait rien.
                    -- Pour inclure le 12/01/2025 en entier, il faut prendre (startDate inclus, endDate + 1 jour exclus)
                    AND tsrange((:startDate)::timestamp, ((:endDate)::timestamp + make_interval(days => 1))::timestamp, \'[)\') && tsrange(p.start_datetime::timestamp, p.end_datetime::timestamp)
                )';

                $parameters['startDate'] = $startDate?->format(\DateTimeInterface::ATOM);
                $parameters['endDate'] = $endDate?->format(\DateTimeInterface::ATOM);
            }
        }

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            \sprintf(
                'SELECT
                    ST_AsGeoJSON(
                        ST_SimplifyPreserveTopology(
                            l.geometry,
                            -- Simplify lines smaller than 3m (0.00001° ~= 1m) to reduce transfer size
                            3 * 0.00001
                        )
                    ) AS geometry,
                    m.type AS measure_type,
                    l.uuid AS location_uuid,
                    ro.category AS regulation_category
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
                    'regulation_category' => $row['regulation_category'],
                ],
            ];
        }

        return json_encode([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }
}
