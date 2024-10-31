<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\SigningAuthority;

use App\Domain\Organization\SigningAuthority\Repository\SigningAuthorityRepositoryInterface;
use App\Domain\Organization\SigningAuthority\SigningAuthority;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class SigningAuthorityRepository extends ServiceEntityRepository implements SigningAuthorityRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SigningAuthority::class);
    }

    public function add(SigningAuthority $signingAuthority): SigningAuthority
    {
        $this->getEntityManager()->persist($signingAuthority);

        return $signingAuthority;
    }

    public function findOneByOrganizationUuid(string $organizationUuid): ?SigningAuthority
    {
        return $this->createQueryBuilder('s')
            ->where('s.organization = :uuid')
            ->setParameter('uuid', $organizationUuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
