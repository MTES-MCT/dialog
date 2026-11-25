<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Anct;

use App\Domain\Anct\Repository\TerritoryContactRepositoryInterface;
use App\Domain\Anct\TerritoryContact;
use App\Infrastructure\Persistence\Doctrine\QueryParameters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class TerritoryContactRepository extends ServiceEntityRepository implements TerritoryContactRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TerritoryContact::class);
    }

    public function isWhitelisted(string $siret, string $email): bool
    {
        $count = $this->createQueryBuilder('tc')
            ->select('COUNT(tc.siret)')
            ->where('tc.siret = :siret')
            ->andWhere('tc.email = :email')
            ->setParameters(QueryParameters::fromArray([
                'siret' => $siret,
                'email' => $email,
            ]))
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
