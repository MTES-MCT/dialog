<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Establishment;

use App\Domain\Organization\Establishment\Establishment;
use App\Domain\Organization\Establishment\Repository\EstablishmentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class EstablishmentRepository extends ServiceEntityRepository implements EstablishmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Establishment::class);
    }

    public function add(Establishment $establishment): void
    {
        $this->getEntityManager()->persist($establishment);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
