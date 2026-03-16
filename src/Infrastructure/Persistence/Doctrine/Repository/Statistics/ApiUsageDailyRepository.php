<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Statistics;

use App\Domain\Statistics\ApiUsageDaily;
use App\Domain\Statistics\Repository\ApiUsageDailyRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ApiUsageDailyRepository extends ServiceEntityRepository implements ApiUsageDailyRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiUsageDaily::class);
    }

    public function findOneByDayAndType(\DateTimeInterface $day, string $type): ?ApiUsageDaily
    {
        $normalized = $this->normalizeToDate($day);

        return $this->createQueryBuilder('a')
            ->where('a.day = :day')
            ->andWhere('a.type = :type')
            ->setParameter('day', $normalized)
            ->setParameter('type', $type)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findNotExportedUntil(\DateTimeInterface $until): array
    {
        $normalized = $this->normalizeToDate($until);

        return $this->createQueryBuilder('a')
            ->where('a.day <= :until')
            ->andWhere('a.exportedAt IS NULL')
            ->setParameter('until', $normalized)
            ->orderBy('a.day', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function add(ApiUsageDaily $apiUsageDaily): void
    {
        $this->getEntityManager()->persist($apiUsageDaily);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    private function normalizeToDate(\DateTimeInterface $day): \DateTimeImmutable
    {
        if ($day instanceof \DateTimeImmutable) {
            return $day->setTime(0, 0, 0);
        }

        return \DateTimeImmutable::createFromMutable($day)->setTime(0, 0, 0);
    }
}
