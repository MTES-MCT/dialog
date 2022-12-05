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

    public function save(RegulationOrder $regulationOrder): RegulationOrder
    {
        $this->getEntityManager()->persist($regulationOrder);

        return $regulationOrder;
    }

    /** @return string[] */
    public function findAllDescriptions(): array
    {
        $results = $this
            ->createQueryBuilder('o')
            ->select('o.description')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult()
        ;

        return array_map(function ($result) {
            return $result['description'];
        }, $results);
    }
}
