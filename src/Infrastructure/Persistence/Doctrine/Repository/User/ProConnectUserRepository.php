<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\User;

use App\Domain\User\ProConnectUser;
use App\Domain\User\Repository\ProConnectUserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ProConnectUserRepository extends ServiceEntityRepository implements ProConnectUserRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, ProConnectUser::class);
    }

    public function add(ProConnectUser $proConnectUser): ProConnectUser
    {
        $this->getEntityManager()->persist($proConnectUser);

        return $proConnectUser;
    }
}
