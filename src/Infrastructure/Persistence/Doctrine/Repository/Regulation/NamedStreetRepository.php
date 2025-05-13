<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Repository\NamedStreetRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class NamedStreetRepository extends ServiceEntityRepository implements NamedStreetRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NamedStreet::class);
    }

    public function add(NamedStreet $namedStreet): NamedStreet
    {
        $this->getEntityManager()->persist($namedStreet);

        return $namedStreet;
    }

    public function findAllWithoutRoadBanIds(): array
    {
        return $this->createQueryBuilder('ns')
            ->where('ns.roadBanId IS NULL OR (ns.fromRoadName IS NOT NULL AND ns.fromRoadBanId IS NULL) OR (ns.toRoadName IS NOT NULL AND ns.toRoadBanId IS NULL)')
            ->getQuery()
            ->getResult()
        ;
    }

    public function delete(NamedStreet $namedStreet): void
    {
        $this->getEntityManager()->remove($namedStreet);
    }
}
