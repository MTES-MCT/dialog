<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Repository\StorageRegulationOrderRepositoryInterface;
use App\Domain\Regulation\StorageRegulationOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class StorageRegulationOrderRepository extends ServiceEntityRepository implements StorageRegulationOrderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StorageRegulationOrder::class);
    }

    public function add(StorageRegulationOrder $storageRegulationOrder): StorageRegulationOrder
    {
        $this->getEntityManager()->persist($storageRegulationOrder);

        return $storageRegulationOrder;
    }

    public function findOneByRegulationOrderUuid(string $uuid): ?StorageRegulationOrder
    {
        return $this->createQueryBuilder('sro')
            ->where('sro.regulationOrder = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('sro.regulationOrder', 'ro')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function remove(StorageRegulationOrder $storageRegulationOrder): void
    {
        $this->getEntityManager()->remove($storageRegulationOrder);
    }
}
