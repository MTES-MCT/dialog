<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\User;

use App\Application\StringUtilsInterface;
use App\Domain\User\AccessRequest;
use App\Domain\User\Repository\AccessRequestRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class AccessRequestRepository extends ServiceEntityRepository implements AccessRequestRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly StringUtilsInterface $stringUtils,
    ) {
        parent::__construct($registry, AccessRequest::class);
    }

    public function add(AccessRequest $accessRequest): AccessRequest
    {
        $this->getEntityManager()->persist($accessRequest);

        return $accessRequest;
    }

    public function remove(AccessRequest $accessRequest): void
    {
        $this->getEntityManager()->remove($accessRequest);
    }

    public function findOneByEmail(string $email): ?AccessRequest
    {
        return $this->createQueryBuilder('a')
            ->where('a.email = :email')
            ->setParameter('email', $this->stringUtils->normalizeEmail($email))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByUuid(string $uuid): ?AccessRequest
    {
        return $this->createQueryBuilder('a')
            ->where('a.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
