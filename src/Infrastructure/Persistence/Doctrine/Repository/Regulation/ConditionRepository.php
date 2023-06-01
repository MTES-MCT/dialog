<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Condition\Condition;
use App\Domain\Regulation\Repository\ConditionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ConditionRepository extends ServiceEntityRepository implements ConditionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Condition::class);
    }

    public function add(Condition $condition): Condition
    {
        $this->getEntityManager()->persist($condition);

        return $condition;
    }
}
