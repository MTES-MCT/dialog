<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
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

        // NamedStreet etc hold a reference to the just-deleted Location object.
        // Detach them to prevent Doctrine from re-creating the Location after the current command finishes.

        if ($ns = $location->getNamedStreet()) {
            $this->getEntityManager()->detach($ns);
        }

        if ($nr = $location->getNumberedRoad()) {
            $this->getEntityManager()->detach($nr);
        }

        if ($rg = $location->getRawGeoJSON()) {
            $this->getEntityManager()->detach($rg);
        }
    }

    public function findOneByUuid(string $uuid): ?Location
    {
        return $this->createQueryBuilder('l')
            ->addSelect('m', 'v', 'p', 'd', 't', 'sa')
            ->innerJoin('l.measure', 'm')
            ->leftJoin('m.periods', 'p')
            ->leftJoin('m.vehicleSet', 'v')
            ->leftJoin('p.dailyRange', 'd')
            ->leftJoin('p.timeSlots', 't')
            ->leftJoin('l.storageArea', 'sa')
            ->where('l.uuid = :uuid')
            // The `periods` association has no mapped order: sort chronologically so the popup
            // lists periods deterministically (oldest first) instead of in DB row order.
            ->addOrderBy('p.startDateTime', 'ASC')
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
        RegulationOrderRecordStatusEnum $status = RegulationOrderRecordStatusEnum::PUBLISHED,
        array $organizationUuids = [],
    ): string {
        [$regulationTypeWhereClause, $measureDatesCondition, $parameters, $types] = $this->buildMapFilterSql(
            $includePermanentRegulations,
            $includeTemporaryRegulations,
            $measureTypes,
            $startDate,
            $endDate,
            $status,
            $organizationUuids,
        );

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
                ORDER BY CASE m.type
                    WHEN \'alternateRoad\' THEN 1
                    WHEN \'parkingProhibited\' THEN 2
                    WHEN \'speedLimitation\' THEN 3
                    WHEN \'noEntry\' THEN 4
                    WHEN \'noOvertaking\' THEN 5
                    ELSE 6
                END
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

        return json_encode(['type' => 'FeatureCollection', 'features' => $features]);
    }

    public function findRestrictionsAsMVT(
        int $z,
        int $x,
        int $y,
        bool $includePermanentRegulations = false,
        bool $includeTemporaryRegulations = false,
        array $measureTypes = [],
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
    ): string {
        [$regulationTypeWhereClause, $measureDatesCondition, $parameters, $types] = $this->buildMapFilterSql(
            $includePermanentRegulations,
            $includeTemporaryRegulations,
            $measureTypes,
            $startDate,
            $endDate,
            RegulationOrderRecordStatusEnum::PUBLISHED,
            [],
        );

        $parameters['z'] = $z;
        $parameters['x'] = $x;
        $parameters['y'] = $y;
        $types['z'] = ParameterType::INTEGER;
        $types['x'] = ParameterType::INTEGER;
        $types['y'] = ParameterType::INTEGER;

        // ST_TileEnvelope produces the tile bounds in EPSG:3857 (Web Mercator), the projection
        // used for vector tiles. We pre-filter geometries with a `&&` (bbox intersection) on the
        // 4326 envelope of the tile to leverage the GIST index on `location.geometry`.
        $sql = \sprintf(
            'WITH bounds AS (
                SELECT
                    ST_TileEnvelope(:z, :x, :y) AS geom_3857,
                    ST_Transform(ST_TileEnvelope(:z, :x, :y), 4326) AS geom_4326
            ),
            mvtgeom AS (
                SELECT
                    ST_AsMVTGeom(
                        ST_Transform(l.geometry, 3857),
                        bounds.geom_3857,
                        extent => 4096,
                        buffer => 64,
                        clip_geom => true
                    ) AS geom,
                    m.type AS measure_type,
                    l.uuid::text AS location_uuid,
                    ro.category AS regulation_category
                FROM bounds, location AS l
                INNER JOIN measure AS m ON m.uuid = l.measure_uuid
                INNER JOIN regulation_order AS ro ON ro.uuid = m.regulation_order_uuid
                INNER JOIN regulation_order_record AS roc ON ro.uuid = roc.regulation_order_uuid
                WHERE roc.status = :status
                AND l.geometry IS NOT NULL
                AND m.type IN (:measureTypes)
                AND l.geometry && bounds.geom_4326
                %s
                %s
            )
            SELECT COALESCE(ST_AsMVT(mvtgeom.*, \'restrictions\', 4096, \'geom\'), \'\'::bytea) AS mvt
            FROM mvtgeom
            WHERE geom IS NOT NULL',
            $regulationTypeWhereClause,
            $measureDatesCondition,
        );

        $row = $this->getEntityManager()->getConnection()->fetchAssociative($sql, $parameters, $types);

        $mvt = $row['mvt'] ?? '';

        // PostgreSQL may come back as a resource (stream) depending on its driver.
        if (\is_resource($mvt)) {
            $mvt = stream_get_contents($mvt);
        }

        return $mvt === false ? '' : (string) $mvt;
    }

    /**
     * @param string[] $organizationUuids
     *
     * @return array{0: string, 1: string, 2: array<string, mixed>, 3: array<string, mixed>}
     */
    private function buildMapFilterSql(
        bool $includePermanentRegulations,
        bool $includeTemporaryRegulations,
        array $measureTypes,
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        RegulationOrderRecordStatusEnum $status,
        array $organizationUuids,
    ): array {
        $parameters = [
            'status' => $status->value,
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

        // Restrict to the given organizations' regulation orders (e.g. to show an organization
        // its own drafts). Empty array means no organization restriction (the public/published map).
        if ($organizationUuids) {
            $regulationTypeWhereClause .= ' AND roc.organization_uuid IN (:organizationUuids)';
            $parameters['organizationUuids'] = $organizationUuids;
            $types['organizationUuids'] = ArrayParameterType::STRING;
        }

        $measureDatesCondition = '';

        if ($startDate || $endDate) {
            if ($startDate && $endDate && $startDate > $endDate) {
                // Renvoie un résultat vide pour éviter une erreur dans le cas où la date de fin est avant la date de début
                $measureDatesCondition = 'AND FALSE';
            } else {
                $rangeStartSql = $startDate ? '(:startDate)::timestamp' : 'NULL';
                $rangeEndSql = $endDate ? '((:endDate)::timestamp + make_interval(days => 1))::timestamp' : 'NULL';

                // Principe : on garde une localisation si l'intervalle défini par ses périodes
                // intersecte au moins partiellement l'intervalle défini par les filtres.
                // En PostgreSQL, tsrange permet de représenter un intervalle de date et heure.
                // https://www.postgresql.org/docs/13/rangetypes.html
                // NB : si l'arrêté n'a pas encore de période, EXISTS renverra FALSE, donc on ne retiendra pas ses localisations, comme attendu.
                // IMPORTANT : si end_datetime est NULL, cela signifie une période permanente (sans fin),
                // on utilise donc un range ouvert [start, +infini)
                $measureDatesCondition = \sprintf(
                    'AND EXISTS (
                    SELECT 1
                    FROM period AS p
                    WHERE p.measure_uuid = m.uuid
                    -- ATTENTION : startDate et endDate sont données comme "inclus" toutes les deux,
                    -- et elles ont une heure qui vaut 00h00.
                    -- Donc dans le cas où startDate et endDate désignent toutes les deux le 12/01/2025 par exemple,
                    -- cela correspondrait à un intervalle de temps vide, et on ne sélectionnerait rien.
                    -- Pour inclure le 12/01/2025 en entier, il faut prendre (startDate inclus, endDate + 1 jour exclus)
                    AND tsrange(%s, %s, \'[)\') && tsrange(
                        LEAST(p.start_datetime::timestamp, p.end_datetime::timestamp),
                        CASE
                            WHEN p.end_datetime IS NULL THEN NULL
                            ELSE GREATEST(p.start_datetime::timestamp, p.end_datetime::timestamp)
                        END,
                        \'[)\'
                    )
                )',
                    $rangeStartSql,
                    $rangeEndSql,
                );

                if ($startDate) {
                    $parameters['startDate'] = $startDate->format(\DateTimeInterface::ATOM);
                }

                if ($endDate) {
                    $parameters['endDate'] = $endDate->format(\DateTimeInterface::ATOM);
                }
            }
        }

        return [$regulationTypeWhereClause, $measureDatesCondition, $parameters, $types];
    }

    public function findGeometriesForRegulationOrderRecord(string $uuid): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT
                ST_AsGeoJSON(l.geometry) AS geometry,
                m.type AS measure_type
            FROM location AS l
            INNER JOIN measure AS m ON m.uuid = l.measure_uuid
            INNER JOIN regulation_order AS ro ON ro.uuid = m.regulation_order_uuid
            INNER JOIN regulation_order_record AS roc ON ro.uuid = roc.regulation_order_uuid
            WHERE roc.uuid = :uuid
            AND l.geometry IS NOT NULL
            ORDER BY l.uuid',
            ['uuid' => $uuid],
        );

        return array_values($rows);
    }

    public function findAllWithoutGeometry(): array
    {
        return $this->createQueryBuilder('l')
            ->addSelect('m', 'ro', 'roc', 'o')
            ->where('l.geometry IS NULL')
            ->innerJoin('l.measure', 'm')
            ->innerJoin('m.regulationOrder', 'ro')
            ->innerJoin('ro.regulationOrderRecord', 'roc')
            ->innerJoin('roc.organization', 'o')
            ->getQuery()
            ->getResult()
        ;
    }
}
