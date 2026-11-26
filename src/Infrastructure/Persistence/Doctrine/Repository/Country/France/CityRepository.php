<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Country\France;

use App\Domain\Country\France\City;
use App\Domain\Country\France\Repository\CityRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\QueryParameters;
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
           ->setParameters(QueryParameters::fromArray([
               'name' => $name,
               'departement' => $departement,
           ]))
           ->setMaxResults(1)
           ->getQuery()
           ->getOneOrNullResult()
        ;
    }
}
