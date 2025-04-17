<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Application\Regulation\View\RegulationOrderTemplateView;
use App\Domain\Regulation\DTO\RegulationOrderTemplateDTO;
use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\Regulation\Repository\RegulationOrderTemplateRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RegulationOrderTemplateRepository extends ServiceEntityRepository implements RegulationOrderTemplateRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegulationOrderTemplate::class);
    }

    public function add(RegulationOrderTemplate $regulationOrderTemplate): RegulationOrderTemplate
    {
        $this->getEntityManager()->persist($regulationOrderTemplate);

        return $regulationOrderTemplate;
    }

    public function findByFilters(RegulationOrderTemplateDTO $dto): array
    {
        $query = $this->createQueryBuilder('rot');
        $query
            ->select(
                \sprintf(
                    'NEW %s(
                        rot.uuid,
                        rot.name,
                        o.uuid
                    )',
                    RegulationOrderTemplateView::class,
                ),
            )
            ->leftJoin('rot.organization', 'o')
            ->orderBy('rot.createdAt', 'DESC');

        if ($dto->organizationUuid) {
            $query->where('o.uuid = :organization OR o.uuid is NULL');
            $query->setParameter('organization', $dto->organizationUuid);
        }

        return $query
            ->getQuery()
            ->getResult()
        ;
    }
}
