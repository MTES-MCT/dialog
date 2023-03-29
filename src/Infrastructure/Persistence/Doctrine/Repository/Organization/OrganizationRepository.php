<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Organization;

use App\Domain\Organization\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrganizationRepository extends ServiceEntityRepository implements OrganizationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    public function findOrganizations(): array
    {
        return $this->createQueryBuilder('o')
        ->addOrderBy('o.name', 'ASC')
        ->getQuery()
        ->getResult()
        ;
    }

    public function save(Organization $organization): Organization
    {
        $this->getEntityManager()->persist($organization);

        return $organization;
    }

    public function findByUuid(string $uuid): Organization|null
    {
        return $this->createQueryBuilder('o')
        ->where('o.uuid = :uuid')
        ->setParameter('uuid', $uuid)
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult()
        ;
    }
}
