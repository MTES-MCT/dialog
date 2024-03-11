<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class LocationRepository extends ServiceEntityRepository implements LocationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    public function add(Location $location): Location
    {
        $this->getEntityManager()->persist($location);

        return $location;
    }

    public function delete(Location $location): void
    {
        $this->getEntityManager()->remove($location);
    }

    public function iterFindAll(): iterable
    {
        // See: https://www.doctrine-project.org/projects/doctrine-orm/en/3.1/reference/batch-processing.html#iterating-large-results-for-data-processing
        $q = $this->createQueryBuilder('l')
            ->select()
            ->getQuery();

        foreach ($q->toIterable() as $row) {
            yield $row;
            $this->_em->detach($row);
        }
    }
}
