<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RegulationOrderRepository extends ServiceEntityRepository implements RegulationOrderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegulationOrder::class);
    }

    public function save(RegulationOrder $regulationOrder): RegulationOrder
    {
        $this->getEntityManager()->persist($regulationOrder);

        return $regulationOrder;
    }
}
