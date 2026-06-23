<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Location\WholeCity;
use App\Domain\Regulation\Repository\WholeCityRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class WholeCityRepository extends ServiceEntityRepository implements WholeCityRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WholeCity::class);
    }

    public function add(WholeCity $wholeCity): WholeCity
    {
        $this->getEntityManager()->persist($wholeCity);

        return $wholeCity;
    }

    public function delete(WholeCity $wholeCity): void
    {
        $this->getEntityManager()->remove($wholeCity);
    }
}
