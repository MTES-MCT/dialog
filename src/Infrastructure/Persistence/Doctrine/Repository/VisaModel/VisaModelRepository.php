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

    public function remove(VisaModel $visaModel): void
    {
        $this->getEntityManager()->remove($visaModel);
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

    public function findOrganizationVisaModels(?string $organizationUuid = null): array
    {
        $query = $this->createQueryBuilder('v')
            ->select(
                \sprintf(
                    'NEW %s(
                        v.uuid,
                        v.name,
                        v.description,
                        o.uuid,
                        o.name
                    )',
                    VisaModelView::class,
                ),
            )
            ->leftJoin('v.organization', 'o')
            ->orderBy('v.organization', 'DESC');

        if ($organizationUuid) {
            $query
                ->where('o.uuid = :uuid OR v.organization IS NULL')
                ->setParameter('uuid', $organizationUuid);
        } else {
            $query->where('v.organization IS NULL');
        }

        return $query->getQuery()->getResult();
    }
}
