<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\User;

use App\Application\User\View\OrganizationUserView;
use App\Application\User\View\UserOrganizationView;
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

    public function findByUserUuid(string $uuid): array
    {
        return $this->createQueryBuilder('ou')
            ->select(\sprintf(
                'NEW %s(
                    o.uuid,
                    o.name,
                    ou.roles
                )',
                UserOrganizationView::class,
            ))
            ->where('ou.user = :userUuid')
            ->innerJoin('ou.organization', 'o')
            ->setParameter('userUuid', $uuid)
            ->getQuery()
            ->getResult();
    }

    public function findByOrganizationUuid(string $uuid): array
    {
        return $this->createQueryBuilder('ou')
            ->select(\sprintf(
                'NEW %s(
                    u.uuid,
                    u.fullName,
                    u.email,
                    ou.roles
                )',
                OrganizationUserView::class,
            ))
            ->where('ou.organization = :organizationUuid')
            ->innerJoin('ou.user', 'u')
            ->setParameter('organizationUuid', $uuid)
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
