<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\France;

use App\Domain\France\City;
use App\Domain\France\Repository\CityRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class CityRepository extends ServiceEntityRepository implements CityRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, City::class);
    }

    public function findOneByNameAndDepartement(string $name, string $departement): ?City
    {
        return $this->createQueryBuilder('c')
           ->where('c.name = :name and c.departement = :departement')
           ->setParameter('name', $name)
           ->setParameter('departement', $departement)
           ->setMaxResults(1)
           ->getQuery()
           ->getOneOrNullResult()
        ;
    }
}
