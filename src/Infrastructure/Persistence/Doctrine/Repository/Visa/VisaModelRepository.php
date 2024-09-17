<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Visa;

use App\Application\Visa\View\VisaModelView;
use App\Domain\Visa\Repository\VisaModelRepositoryInterface;
use App\Domain\Visa\VisaModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class VisaModelRepository extends ServiceEntityRepository implements VisaModelRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VisaModel::class);
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
