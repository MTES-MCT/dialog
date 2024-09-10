<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\VisaModel;

use App\Application\VisaModel\View\VisaModelView;
use App\Domain\VisaModel\Repository\VisaModelRepositoryInterface;
use App\Domain\VisaModel\VisaModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class VisaModelRepository extends ServiceEntityRepository implements VisaModelRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VisaModel::class);
    }

    public function add(VisaModel $visaModel): VisaModel
    {
        $this->getEntityManager()->persist($visaModel);

        return $visaModel;
    }

    public function findOneByUuid(string $uuid): ?VisaModel
    {
        return $this->createQueryBuilder('v')
            ->where('v.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOrganizationVisaModels(string $organizationUuid): array
    {
        return $this->createQueryBuilder('v')
            ->select(
                \sprintf(
                    'NEW %s(
                        v.uuid,
                        v.name,
                        v.description
                    )',
                    VisaModelView::class,
                ),
            )
            ->where('o.uuid = :uuid')
            ->setParameter('uuid', $organizationUuid)
            ->innerJoin('v.organization', 'o')
            ->getQuery()
            ->getResult()
        ;
    }
}