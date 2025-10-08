<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Organization;

use App\Domain\Organization\ApiClient;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ApiClientRepository extends ServiceEntityRepository implements ApiClientRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiClient::class);
    }

    public function findOneByClientId(string $clientId): ?ApiClient
    {
        return $this->createQueryBuilder('a')
            ->where('a.clientId = :clientId')
            ->setParameter('clientId', $clientId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
