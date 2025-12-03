<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\User;

use App\Domain\User\ReportAddress;
use App\Domain\User\Repository\ReportAddressRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ReportAddressRepository extends ServiceEntityRepository implements ReportAddressRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReportAddress::class);
    }

    public function add(ReportAddress $reportAddress): ReportAddress
    {
        $this->getEntityManager()->persist($reportAddress);

        return $reportAddress;
    }
}
