<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\User;

use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class OrganizationUserRepository extends ServiceEntityRepository implements OrganizationUserRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, OrganizationUser::class);
    }

    public function add(OrganizationUser $organizationUser): void
    {
        $this->getEntityManager()->persist($organizationUser);
    }

    public function remove(OrganizationUser $organizationUser): void
    {
        $this->getEntityManager()->remove($organizationUser);
    }

    public function findbyUserUuid(string $userUuid): array
    {
        return $this->createQueryBuilder('ou')
            ->addSelect('o')
            ->where('ou.user = :userUuid')
            ->innerJoin('ou.organization', 'o')
            ->setParameter('userUuid', $userUuid)
            ->getQuery()
            ->getResult();
    }

    public function findByOrganizationUuid(string $uuid): array
    {
        return $this->createQueryBuilder('ou')
            ->addSelect('u')
            ->where('ou.organization = :organization')
            ->innerJoin('ou.user', 'u')
            ->setParameter('organization', $uuid)
            ->orderBy('u.fullName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOrganizationUser(string $organizationUuid, string $userUuid): ?OrganizationUser
    {
        return $this->createQueryBuilder('ou')
            ->addSelect('u', 'o')
            ->where('o.uuid = :organizationUuid')
            ->andWhere('ou.user = :userUuid')
            ->innerJoin('ou.user', 'u')
            ->innerJoin('ou.organization', 'o')
            ->setParameters([
                'organizationUuid' => $organizationUuid,
                'userUuid' => $userUuid,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByEmailAndOrganization(string $email, string $organizationUuid): ?OrganizationUser
    {
        return $this->createQueryBuilder('ou')
            ->where('o.uuid = :organizationUuid')
            ->andWhere('u.email = :email')
            ->innerJoin('ou.user', 'u')
            ->innerJoin('ou.organization', 'o')
            ->setParameters([
                'organizationUuid' => $organizationUuid,
                'email' => $email,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
