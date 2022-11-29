<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\RegulationOrder;

use App\Domain\RegulationOrder\RegulationOrder;
use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RegulationOrderRepository extends ServiceEntityRepository implements RegulationOrderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegulationOrder::class);
    }

    public function save(RegulationOrder $regulationOrder): void
    {
        $this->getEntityManager()->persist($regulationOrder);
    }

    public function findAll(): array
    {
        return $this
            ->createQueryBuilder('o')
            ->select('partial o.{uuid, description, issuingAuthority}')
            ->getQuery()
            ->getResult();
    }

    public function findOneById(string $uuid): ?RegulationOrder
    {
        return $this
        ->createQueryBuilder('o')
        ->select('partial o.{uuid, description, issuingAuthority}')
        ->where('o.uuid = :uuid')
        ->setParameter('uuid', $uuid)
        ->getQuery()
        ->getOneOrNullResult();
    }
}
