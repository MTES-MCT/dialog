<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Organization;

use App\Domain\Organization\ApiClient;
use App\Domain\Organization\Repository\ApiClientRepositoryInterface;
use App\Domain\User\Organization;
use App\Domain\User\User;
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

    /**
     * @return ApiClient[]
     */
    public function findByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('a')
            ->addSelect('u')
            ->leftJoin('a.user', 'u')
            ->where('a.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByOrganizationAndUser(Organization $organization, User $user): ?ApiClient
    {
        return $this->createQueryBuilder('a')
            ->where('a.organization = :organization')
            ->andWhere('a.user = :user')
            ->setParameter('organization', $organization)
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByUuid(string $uuid): ?ApiClient
    {
        return $this->createQueryBuilder('a')
            ->addSelect('o', 'u')
            ->leftJoin('a.organization', 'o')
            ->leftJoin('a.user', 'u')
            ->where('a.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function add(ApiClient $apiClient): void
    {
        $this->getEntityManager()->persist($apiClient);
    }

    public function remove(ApiClient $apiClient): void
    {
        $this->getEntityManager()->remove($apiClient);
    }
}
