<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Organization;

use App\Application\User\View\OrganizationView;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class OrganizationRepository extends ServiceEntityRepository implements OrganizationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
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
            ->where('o.uuid <> \'e0d93630-acf7-4722-81e8-ff7d5fa64b66\'') // DiaLog
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
}
