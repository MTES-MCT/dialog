<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\User;

use App\Application\User\View\InvitationView;
use App\Domain\User\Invitation;
use App\Domain\User\Organization;
use App\Domain\User\Repository\InvitationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class InvitationRepository extends ServiceEntityRepository implements InvitationRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Invitation::class);
    }

    public function add(Invitation $invitation): Invitation
    {
        $this->getEntityManager()->persist($invitation);

        return $invitation;
    }

    public function delete(Invitation $invitation): void
    {
        $this->getEntityManager()->remove($invitation);
    }

    public function findOneByUuid(string $uuid): ?Invitation
    {
        return $this->createQueryBuilder('i')
            ->where('i.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByEmailAndOrganization(string $email, Organization $organization): ?Invitation
    {
        return $this->createQueryBuilder('i')
            ->where('i.email = :email')
            ->andWhere('i.organization = :organization')
            ->setParameters([
                'organization' => $organization,
                'email' => $email,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByOrganizationUuid(string $uuid): array
    {
        return $this->createQueryBuilder('i')
            ->select(\sprintf(
                'NEW %s(
                    i.uuid,
                    i.fullName,
                    i.email,
                    i.role
                )',
                InvitationView::class,
            ))
            ->where('i.organization = :organizationUuid')
            ->innerJoin('i.owner', 'u')
            ->setParameter('organizationUuid', $uuid)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
