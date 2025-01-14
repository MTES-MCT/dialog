<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\User;

use App\Domain\User\PasswordUser;
use App\Domain\User\Repository\PasswordUserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class PasswordUserRepository extends ServiceEntityRepository implements PasswordUserRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, PasswordUser::class);
    }

    public function add(PasswordUser $passwordUser): PasswordUser
    {
        $this->getEntityManager()->persist($passwordUser);

        return $passwordUser;
    }
}
