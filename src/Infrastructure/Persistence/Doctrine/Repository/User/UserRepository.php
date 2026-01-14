<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\User;

use App\Application\StringUtilsInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use App\Domain\User\UserExportView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly StringUtilsInterface $stringUtils,
    ) {
        parent::__construct($registry, User::class);
    }

    public function add(User $user): User
    {
        $this->getEntityManager()->persist($user);

        return $user;
    }

    public function remove(User $user): void
    {
        $this->getEntityManager()->remove($user);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $this->stringUtils->normalizeEmail($email))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countUsers(): int
    {
        return $this->createQueryBuilder('u')
            ->select('count(DISTINCT(u.uuid))')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllForStatistics(): array
    {
        return $this->getEntityManager()
            ->getConnection()
            ->fetchAllAssociative(
                'SELECT uuid_generate_v4() AS uuid, u.last_active_at AS last_active_at
                FROM "user" AS u',
            );
    }

    public function findAllForExport(): array
    {
        return $this->createQueryBuilder('u')
            ->select('NEW ' . UserExportView::class . '(u.fullName, u.email)')
            ->getQuery()
            ->getResult()
        ;
    }
}
