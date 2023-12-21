<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Organization;

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

    public function add(Organization $organization): void
    {
        $this->getEntityManager()->persist($organization);
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
}
