<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\RegulationOrderIssue;
use App\Domain\Regulation\Repository\RegulationOrderIssueRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RegulationOrderIssueRepository extends ServiceEntityRepository implements RegulationOrderIssueRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegulationOrderIssue::class);
    }

    public function add(RegulationOrderIssue $regulationorderissue): RegulationOrderIssue
    {
        $this->getEntityManager()->persist($regulationorderissue);

        return $regulationorderissue;
    }
}
