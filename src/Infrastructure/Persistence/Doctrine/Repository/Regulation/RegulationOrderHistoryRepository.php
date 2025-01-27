<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\RegulationOrderHistory;
use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RegulationOrderHistoryRepository extends ServiceEntityRepository implements RegulationOrderHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegulationOrderHistory::class);
    }

    public function add(RegulationOrderHistory $regulationOrderHistory): RegulationOrderHistory
    {
        $this->getEntityManager()->persist($regulationOrderHistory);

        return $regulationOrderHistory;
    }
}
