<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Geography;

use App\Application\Geography\View\AdministrativeBoundaryView;
use App\Application\Organization\View\MapBboxView;
use App\Domain\Geography\AdministrativeBoundary;
use App\Domain\Geography\Repository\AdministrativeBoundaryRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class AdministrativeBoundaryRepository extends ServiceEntityRepository implements AdministrativeBoundaryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrativeBoundary::class);
    }

    public function findOneByCode(string $codeType, string $code): ?AdministrativeBoundaryView
    {
        // On ne sélectionne pas la géométrie : elle peut peser plusieurs centaines de Ko.
        return $this->createQueryBuilder('ab')
            ->select(\sprintf('NEW %s(ab.codeType, ab.code, ab.name)', AdministrativeBoundaryView::class))
            ->where('ab.codeType = :codeType')
            ->andWhere('ab.code = :code')
            ->setParameters([
                'codeType' => $codeType,
                'code' => $code,
            ])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAllViews(): array
    {
        return $this->createQueryBuilder('ab')
            ->select(\sprintf('NEW %s(ab.codeType, ab.code, ab.name)', AdministrativeBoundaryView::class))
            ->orderBy('ab.codeType')
            ->addOrderBy('ab.code')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(string $codeType, string $code, string $name, string $geometry, \DateTimeInterface $updatedAt): void
    {
        // Deux requêtes simultanées peuvent demander pour la première fois le même contour :
        // l'upsert évite une violation de clé primaire.
        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT INTO administrative_boundary (code_type, code, name, geometry, updated_at)
            VALUES (:codeType, :code, :name, ST_MakeValid(ST_SetSRID(ST_GeomFromGeoJSON(:geometry), 4326)), :updatedAt)
            ON CONFLICT (code_type, code) DO UPDATE SET
                name = EXCLUDED.name,
                geometry = EXCLUDED.geometry,
                updated_at = EXCLUDED.updated_at',
            [
                'codeType' => $codeType,
                'code' => $code,
                'name' => $name,
                'geometry' => $geometry,
                'updatedAt' => $updatedAt->format(\DateTimeInterface::ATOM),
            ],
        );
    }

    public function delete(string $codeType, string $code): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'DELETE FROM administrative_boundary WHERE code_type = :codeType AND code = :code',
            [
                'codeType' => $codeType,
                'code' => $code,
            ],
        );
    }

    public function findMapBbox(string $codeType, string $code): ?MapBboxView
    {
        $row = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT
                ST_XMin(env) AS min_lon,
                ST_YMin(env) AS min_lat,
                ST_XMax(env) AS max_lon,
                ST_YMax(env) AS max_lat
            FROM (
                SELECT ST_Envelope(ab.geometry) AS env
                FROM administrative_boundary AS ab
                WHERE ab.code_type = :codeType
                AND ab.code = :code
                AND NOT ST_IsEmpty(ab.geometry)
            ) AS t',
            [
                'codeType' => $codeType,
                'code' => $code,
            ],
        );

        if (!$row || $row['min_lon'] === null) {
            return null;
        }

        return new MapBboxView(
            minLon: (float) $row['min_lon'],
            minLat: (float) $row['min_lat'],
            maxLon: (float) $row['max_lon'],
            maxLat: (float) $row['max_lat'],
        );
    }
}
