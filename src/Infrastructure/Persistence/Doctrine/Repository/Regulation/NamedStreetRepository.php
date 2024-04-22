<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Repository\NamedStreetRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class NamedStreetRepository extends ServiceEntityRepository implements NamedStreetRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NamedStreet::class);
    }

    public function add(NamedStreet $namedstreet): NamedStreet
    {
        $this->getEntityManager()->persist($namedstreet);

        return $namedstreet;
    }

    public function delete(NamedStreet $namedstreet): void
    {
        $this->getEntityManager()->remove($namedstreet);
    }
}
