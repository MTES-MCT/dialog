<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Location\Zone;
use App\Domain\Regulation\Repository\ZoneRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ZoneRepository extends ServiceEntityRepository implements ZoneRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Zone::class);
    }

    public function add(Zone $zone): Zone
    {
        $this->getEntityManager()->persist($zone);

        return $zone;
    }

    public function delete(Zone $zone): void
    {
        $this->getEntityManager()->remove($zone);
    }
}
