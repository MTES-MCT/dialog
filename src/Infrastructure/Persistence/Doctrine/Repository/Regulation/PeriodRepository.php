<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Condition\Period\Period;
use App\Domain\Regulation\Repository\PeriodRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class PeriodRepository extends ServiceEntityRepository implements PeriodRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Period::class);
    }

    public function add(Period $period): Period
    {
        $this->getEntityManager()->persist($period);

        return $period;
    }

    public function delete(Period $period): void
    {
        $this->getEntityManager()->remove($period);
    }
}
