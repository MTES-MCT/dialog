<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Regulation\Repository\TimeSlotRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class TimeSlotRepository extends ServiceEntityRepository implements TimeSlotRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeSlot::class);
    }

    public function delete(TimeSlot $timeSlot): void
    {
        $this->getEntityManager()->remove($timeSlot);
    }
}
