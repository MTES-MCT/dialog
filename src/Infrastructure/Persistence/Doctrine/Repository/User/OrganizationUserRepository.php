<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\User;

use App\Application\User\View\UserOrganizationView;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\User;
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

    public function findOrganizationsByUser(User $user): array
    {
        return $this->createQueryBuilder('ou')
            ->select(
                sprintf('NEW %s(o.uuid, o.name, ou.roles)',
                    UserOrganizationView::class,
                ),
            )
            ->where('ou.user = :user')
            ->innerJoin('ou.organization', 'o')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
