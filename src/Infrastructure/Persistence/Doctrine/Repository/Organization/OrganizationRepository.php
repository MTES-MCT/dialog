<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Organization;

use App\Application\User\View\OrganizationView;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
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

    public function computeCentroidFromGeoJson(string $geoJson): string
    {
        $result = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT ST_AsGeoJSON(ST_PointOnSurface(ST_SetSRID(ST_GeomFromGeoJSON(:geoJson), 4326))) AS centroid',
            ['geoJson' => $geoJson],
        );

        return $result['centroid'];
    }
}
