<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Condition;

use App\Domain\Condition\RegulationCondition;
use App\Domain\Condition\Repository\RegulationConditionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RegulationConditionRepository extends ServiceEntityRepository implements RegulationConditionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegulationCondition::class);
    }

    public function save(RegulationCondition $regulationCondition): RegulationCondition
    {
        $this->getEntityManager()->persist($regulationCondition);

        return $regulationCondition;
    }
}
