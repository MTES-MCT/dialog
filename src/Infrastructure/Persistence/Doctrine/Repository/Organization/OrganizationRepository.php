<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Organization;

use App\Application\Organization\View\MapBboxView;
use App\Application\User\View\OrganizationView;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class OrganizationRepository extends ServiceEntityRepository implements OrganizationRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly string $dialogOrgId,
    ) {
        parent::__construct($registry, Organization::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('o')
            ->select(\sprintf(
                'NEW %s(
                    o.uuid,
                    o.name,
                    o.siret
                )',
                OrganizationView::class,
            ))
            ->where('o.siret IS NOT NULL')
            ->orderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllEntities(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.siret is NOT NULL')
            ->getQuery()
            ->getResult();
    }

    public function add(Organization $organization): void
    {
        $this->getEntityManager()->persist($organization);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function findOneByUuid(string $uuid): ?Organization
    {
        return $this->createQueryBuilder('o')
            ->where('o.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneBySiret(string $siret): ?Organization
    {
        return $this->createQueryBuilder('o')
            ->where('o.siret = :siret')
            ->setParameter('siret', $siret)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByName(string $name): ?Organization
    {
        return $this->createQueryBuilder('o')
            ->where('o.name = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function countOrganizations(): int
    {
        return $this->createQueryBuilder('o')
            ->select('count(o.uuid)')
            ->where('o.uuid <> :dialogOrgId')
            ->setParameter('dialogOrgId', $this->dialogOrgId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function canInterveneOnGeometry(string $uuid, string $geometry): bool
    {
        $result = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT CASE
                WHEN ST_IsEmpty(geometry) OR geometry IS NULL THEN false
                ELSE ST_Intersects(ST_GeomFromGeoJSON(:geometry), geometry)
            END as has_intersection
            FROM organization
            WHERE uuid = :uuid',
            [
                'uuid' => $uuid,
                'geometry' => $geometry,
            ],
        );

        return (bool) ($result['has_intersection'] ?? false);
    }

    public function findAllForStatistics(): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            // On regroupe les organisations en \"gros blocs\" géographiques :
            // 1. ST_ClusterDBSCAN crée des clusters d'entités voisines (eps ≈ 100 m en WGS84)
            // 2. Pour chaque cluster, on fusionne les géométries avec ST_Union
            // 3. On construit un nom de cluster en concaténant les noms des organisations
            // 4. On renvoie un GeoJSON par cluster, avec uniquement ce nom agrégé
            'WITH clustered AS (
                SELECT
                    ST_ClusterDBSCAN(geometry, eps := 0.001, minpoints := 1) OVER () AS cluster_id,
                    geometry,
                    name
                FROM organization
                WHERE uuid <> :dialogOrgId
                AND code_type <> :regionCodeType
                AND geometry IS NOT NULL
                AND NOT ST_IsEmpty(geometry)
            )
            SELECT
                cluster_id,
                ST_AsGeoJSON(ST_Union(geometry)) AS geometry,
                string_agg(DISTINCT name, \', \' ORDER BY name) AS cluster_name
            FROM clustered
            GROUP BY cluster_id
            ORDER BY cluster_id',
            [
                'dialogOrgId' => $this->dialogOrgId,
                'regionCodeType' => OrganizationCodeTypeEnum::REGION->value,
            ],
        );
    }

    public function findAllForMetabaseExport(): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select(
                'o.uuid AS organization_uuid',
                'o.name AS organization_name',
                'o.codeType AS code_type',
                '(SELECT COUNT(ou.uuid) FROM App\Domain\User\OrganizationUser ou WHERE ou.organization = o) AS nb_users',
                '(SELECT COUNT(ror.uuid) FROM App\Domain\Regulation\RegulationOrderRecord ror WHERE ror.organization = o AND ror.status = :published) AS nb_published_regulation_orders',
            )
            ->where('o.uuid <> :dialogOrgId')
            ->setParameter('dialogOrgId', $this->dialogOrgId)
            ->setParameter('published', RegulationOrderRecordStatusEnum::PUBLISHED->value);

        return $qb->getQuery()->getResult();
    }

    public function computeCentroidFromGeoJson(string $geoJson): string
    {
        $result = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT ST_AsGeoJSON(ST_PointOnSurface(ST_SetSRID(ST_GeomFromGeoJSON(:geoJson), 4326))) AS centroid',
            ['geoJson' => $geoJson],
        );

        return $result['centroid'];
    }

    public function findInitialMapBbox(?string $userUuid): ?MapBboxView
    {
        $connection = $this->getEntityManager()->getConnection();

        if ($userUuid !== null) {
            $row = $connection->fetchAssociative(
                'SELECT
                    ST_XMin(env) AS min_lon,
                    ST_YMin(env) AS min_lat,
                    ST_XMax(env) AS max_lon,
                    ST_YMax(env) AS max_lat
                FROM (
                    SELECT ST_Envelope(o.geometry) AS env, ou.uuid AS ou_uuid
                    FROM organizations_users AS ou
                    INNER JOIN organization AS o ON o.uuid = ou.organization_uuid
                    WHERE ou.user_uuid = :userUuid
                    AND o.geometry IS NOT NULL
                    AND NOT ST_IsEmpty(o.geometry)
                ) AS t
                ORDER BY ou_uuid
                LIMIT 1',
                ['userUuid' => $userUuid],
            );

            return $row ? $this->bboxRowToView($row) : null;
        }

        $row = $connection->fetchAssociative(
            'SELECT min_lon, min_lat, max_lon, max_lat
            FROM top_published_organization
            ORDER BY random()
            LIMIT 1',
        );

        return $row ? $this->bboxRowToView($row) : null;
    }

    public function findMapBboxByOrganizationUuid(string $organizationUuid): ?MapBboxView
    {
        $connection = $this->getEntityManager()->getConnection();

        $row = $connection->fetchAssociative(
            'SELECT
                ST_XMin(env) AS min_lon,
                ST_YMin(env) AS min_lat,
                ST_XMax(env) AS max_lon,
                ST_YMax(env) AS max_lat
            FROM (
                SELECT ST_Envelope(o.geometry) AS env
                FROM organization AS o
                WHERE o.uuid = :organizationUuid
                AND o.geometry IS NOT NULL
                AND NOT ST_IsEmpty(o.geometry)
            ) AS t',
            ['organizationUuid' => $organizationUuid],
        );

        return $row && $row['min_lon'] !== null ? $this->bboxRowToView($row) : null;
    }

    public function refreshTopPublishedOrganizations(int $limit = 10): void
    {
        $connection = $this->getEntityManager()->getConnection();

        $connection->transactional(function ($connection) use ($limit): void {
            $connection->executeStatement('TRUNCATE TABLE top_published_organization');

            $connection->executeStatement(
                'INSERT INTO top_published_organization (organization_uuid, nb_published, min_lon, min_lat, max_lon, max_lat, refreshed_at)
                SELECT
                    o.uuid,
                    COUNT(roc.uuid) AS nb_published,
                    ST_XMin(ST_Envelope(o.geometry)),
                    ST_YMin(ST_Envelope(o.geometry)),
                    ST_XMax(ST_Envelope(o.geometry)),
                    ST_YMax(ST_Envelope(o.geometry)),
                    now()
                FROM organization AS o
                INNER JOIN regulation_order_record AS roc ON roc.organization_uuid = o.uuid
                WHERE roc.status = :published
                AND o.uuid <> :dialogOrgId
                AND o.geometry IS NOT NULL
                AND NOT ST_IsEmpty(o.geometry)
                GROUP BY o.uuid, o.geometry
                ORDER BY nb_published DESC
                LIMIT :limit',
                [
                    'published' => RegulationOrderRecordStatusEnum::PUBLISHED->value,
                    'dialogOrgId' => $this->dialogOrgId,
                    'limit' => $limit,
                ],
                [
                    'limit' => \PDO::PARAM_INT,
                ],
            );
        });
    }

    /**
     * @param array<string, mixed> $row
     */
    private function bboxRowToView(array $row): MapBboxView
    {
        return new MapBboxView(
            minLon: (float) $row['min_lon'],
            minLat: (float) $row['min_lat'],
            maxLon: (float) $row['max_lon'],
            maxLat: (float) $row['max_lat'],
        );
    }
}
