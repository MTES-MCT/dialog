<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\User;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', trim(strtolower($email)))
            ->innerJoin('u.organizations', 'o')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    public function findUsers(): array
    {
        return $this->createQueryBuilder('u')
        ->getQuery()
        ->getResult()
        ;
    }
    public function save(User $user): User
    {
        $this->getEntityManager()->persist($user);

        return $user;
    }
    public function findUserByUuid(string $uuid): User| null
    {
        return $this->createQueryBuilder('u')
            ->where('u.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('u.organizations', 'o')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function delete(User $user):void
    {
        $this->getEntityManager()->remove($user);
    }
}
