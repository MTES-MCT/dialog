<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Location\StorageArea;
use App\Domain\Regulation\Repository\StorageAreaRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class StorageAreaRepository extends ServiceEntityRepository implements StorageAreaRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, StorageArea::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('sa')
            ->getQuery()
            ->getResult()
        ;
    }
}
