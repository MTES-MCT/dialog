<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Repository\NumberedRoadRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class NumberedRoadRepository extends ServiceEntityRepository implements NumberedRoadRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NumberedRoad::class);
    }

    public function add(NumberedRoad $numberedRoad): NumberedRoad
    {
        $this->getEntityManager()->persist($numberedRoad);

        return $numberedRoad;
    }

    public function delete(NumberedRoad $numberedRoad): void
    {
        $this->getEntityManager()->remove($numberedRoad);
    }
}
