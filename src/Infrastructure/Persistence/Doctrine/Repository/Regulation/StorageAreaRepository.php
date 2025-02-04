<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Location\StorageArea;
use App\Domain\Regulation\Repository\StorageAreaRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

final class StorageAreaRepository extends ServiceEntityRepository implements StorageAreaRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, StorageArea::class);
    }

    public function findAllByRoadNumbers(array $roadNumbers = []): array
    {
        $query = $this->createQueryBuilder('sa');

        if ($roadNumbers) {
            $query = $query
                ->where('sa.roadNumber IN (:roadNumbers)')
                ->setParameter('roadNumbers', $roadNumbers, ArrayParameterType::STRING);
        }

        /** @var StorageArea[] */
        $storageAreas = $query
            ->getQuery()
            ->getResult()
        ;

        $result = [];

        foreach ($storageAreas as $storageArea) {
            $roadNumber = $storageArea->getRoadNumber();

            if (empty($result[$roadNumber])) {
                $result[$roadNumber] = [];
            }

            $result[$roadNumber][] = $storageArea;
        }

        return $result;
    }
}
