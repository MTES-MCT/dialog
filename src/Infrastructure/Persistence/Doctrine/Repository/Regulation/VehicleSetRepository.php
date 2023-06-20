<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Repository\VehicleSetRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class VehicleSetRepository extends ServiceEntityRepository implements VehicleSetRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleSet::class);
    }

    public function add(VehicleSet $vehicleSet): VehicleSet
    {
        $this->getEntityManager()->persist($vehicleSet);

        return $vehicleSet;
    }
}
