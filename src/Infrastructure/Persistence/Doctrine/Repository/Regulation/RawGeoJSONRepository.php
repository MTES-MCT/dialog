<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Location\RawGeoJSON;
use App\Domain\Regulation\Repository\RawGeoJSONRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RawGeoJSONRepository extends ServiceEntityRepository implements RawGeoJSONRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RawGeoJSON::class);
    }

    public function add(RawGeoJSON $rawGeoJSON): RawGeoJSON
    {
        $this->getEntityManager()->persist($rawGeoJSON);

        return $rawGeoJSON;
    }

    public function delete(RawGeoJSON $rawGeoJSON): void
    {
        $this->getEntityManager()->remove($rawGeoJSON);
    }
}
