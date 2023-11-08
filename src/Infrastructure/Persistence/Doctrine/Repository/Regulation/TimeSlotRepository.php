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

    public function add(TimeSlot $timeslot): TimeSlot
    {
        $this->getEntityManager()->persist($timeslot);

        return $timeslot;
    }

    public function delete(TimeSlot $timeslot): void
    {
        $this->getEntityManager()->remove($timeslot);
    }
}
