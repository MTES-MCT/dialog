<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\LocationNew;
use App\Domain\Regulation\Repository\LocationNewRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class LocationNewRepository extends ServiceEntityRepository implements LocationNewRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LocationNew::class);
    }

    public function add(LocationNew $location): LocationNew
    {
        $this->getEntityManager()->persist($location);

        return $location;
    }
}