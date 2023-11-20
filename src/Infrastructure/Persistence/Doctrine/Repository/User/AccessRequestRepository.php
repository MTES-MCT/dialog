<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\User;

use App\Domain\User\AccessRequest;
use App\Domain\User\Repository\AccessRequestRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class AccessRequestRepository extends ServiceEntityRepository implements AccessRequestRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessRequest::class);
    }

    public function add(AccessRequest $accessRequest): AccessRequest
    {
        $this->getEntityManager()->persist($accessRequest);

        return $accessRequest;
    }

    public function findOneByEmail(string $email): ?AccessRequest
    {
        return $this->createQueryBuilder('a')
            ->where('a.email = :email')
            ->setParameter('email', trim(strtolower($email)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
