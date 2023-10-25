<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Condition\Period\DailyRange;
use App\Domain\Regulation\Repository\DailyRangeRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class DailyRangeRepository extends ServiceEntityRepository implements DailyRangeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyRange::class);
    }

    public function add(DailyRange $dailyRange): DailyRange
    {
        $this->getEntityManager()->persist($dailyRange);

        return $dailyRange;
    }

    public function delete(DailyRange $dailyRange): void
    {
        $this->getEntityManager()->remove($dailyRange);
    }
}
