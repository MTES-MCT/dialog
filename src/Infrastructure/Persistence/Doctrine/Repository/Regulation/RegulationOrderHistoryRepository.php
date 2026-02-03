<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Enum\ActionTypeEnum;
use App\Domain\Regulation\RegulationOrderHistory;
use App\Domain\Regulation\Repository\RegulationOrderHistoryRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RegulationOrderHistoryRepository extends ServiceEntityRepository implements RegulationOrderHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegulationOrderHistory::class);
    }

    public function add(RegulationOrderHistory $regulationOrderHistory): RegulationOrderHistory
    {
        $this->getEntityManager()->persist($regulationOrderHistory);

        return $regulationOrderHistory;
    }

    public function findLastRegulationOrderHistoryByUuid(string $regulationOrderUuid): ?array
    {
        return $this->createQueryBuilder('roh')
            ->select(' roh.action, roh.date')
            ->where('roh.regulationOrderUuid = :uuid')
            ->setParameter('uuid', $regulationOrderUuid)
            ->orderBy('roh.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findPublicationDatesByRegulationOrderUuids(array $regulationOrderUuids): array
    {
        if (0 === \count($regulationOrderUuids)) {
            return [];
        }

        $results = $this->createQueryBuilder('roh')
            ->select('roh.regulationOrderUuid, MAX(roh.date) as publication_date')
            ->where('roh.regulationOrderUuid IN (:uuids)')
            ->andWhere('roh.action = :action')
            ->setParameter('uuids', $regulationOrderUuids)
            ->setParameter('action', ActionTypeEnum::PUBLISH->value)
            ->groupBy('roh.regulationOrderUuid')
            ->getQuery()
            ->getResult();

        $map = [];

        foreach ($results as $row) {
            $date = $row['publication_date'];
            $map[$row['regulationOrderUuid']] = $date instanceof \DateTimeInterface
                ? $date->format(\DateTimeInterface::ATOM)
                : (string) $date;
        }

        return $map;
    }

    public function countCreatedRegulationOrdersByUserUuids(array $userUuids): array
    {
        if (0 === \count($userUuids)) {
            return [];
        }

        $results = $this->createQueryBuilder('roh')
            ->select('roh.userUuid, COUNT(DISTINCT roh.regulationOrderUuid) as count')
            ->where('roh.userUuid IN (:userUuids)')
            ->andWhere('roh.action = :action')
            ->setParameter('userUuids', $userUuids)
            ->setParameter('action', ActionTypeEnum::CREATE->value)
            ->groupBy('roh.userUuid')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['userUuid']] = (int) $row['count'];
        }

        return $counts;
    }
}
