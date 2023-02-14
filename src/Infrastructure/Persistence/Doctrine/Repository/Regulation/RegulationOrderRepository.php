<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\User\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RegulationOrderRepository extends ServiceEntityRepository implements RegulationOrderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegulationOrder::class);
    }

    public function findRegulationsByOrganization(
        Organization $organization,
        int $maxItemsPerPage,
        int $page,
        string $status,
    ): array {
        return $this->createQueryBuilder('ro')
            ->select('ro.uuid, o.startPeriod, o.endPeriod, roc.status, l.city, l.roadName')
            ->where('roc.status = :status')
            ->andWhere('roc.organization = :organization')
            ->setParameters(['status' => $status, 'organization' => $organization->getUuid()])
            ->innerJoin('ro.regulationOrderRecord', 'roc')
            ->innerJoin('ro.regulationCondition', 'rc')
            ->leftJoin('rc.overallPeriod', 'o')
            ->leftJoin('rc.location', 'l')
            ->orderBy('o.startPeriod', 'DESC')
            ->setFirstResult($maxItemsPerPage * ($page - 1))
            ->setMaxResults($maxItemsPerPage)
            ->getQuery()
            ->getResult()
        ;
    }

    public function countRegulationsByOrganization(Organization $organization, string $status): int
    {
        return $this->createQueryBuilder('ro')
            ->select('count(ro.uuid)')
            ->where('roc.status = :status')
            ->andWhere('roc.organization = :organization')
            ->setParameters(['status' => $status, 'organization' => $organization->getUuid()])
            ->innerJoin('ro.regulationOrderRecord', 'roc')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findOneByUuid(string $uuid): RegulationOrder|null
    {
        return $this->createQueryBuilder('ro')
            ->where('ro.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('ro.regulationOrderRecord', 'roc')
            ->innerJoin('ro.regulationCondition', 'rc')
            ->innerJoin('roc.organization', 'o')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneForSummary(string $uuid): array|null
    {
        return $this->createQueryBuilder('ro')
            ->select(
                'ro.uuid',
                'roc.status',
                'ro.description',
                'o.startPeriod',
                'o.endPeriod',
                'l.postalCode',
                'l.city',
                'l.roadName',
                'l.fromHouseNumber',
                'l.toHouseNumber',
                'vc.maxWeight',
                'vc.maxHeight',
                'vc.maxWidth',
                'vc.maxLength',
            )
            ->where('ro.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('ro.regulationOrderRecord', 'roc')
            ->innerJoin('ro.regulationCondition', 'rc')
            ->leftJoin('rc.vehicleCharacteristics', 'vc')
            ->leftJoin('rc.overallPeriod', 'o')
            ->leftJoin('rc.location', 'l')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findRegulationOrdersForDatexFormat(): array
    {
        return $this->createQueryBuilder('ro')
            ->select(
                'ro.uuid',
                'ro.issuingAuthority',
                'ro.description',
                'o.startPeriod',
                'o.endPeriod',
                'loc.postalCode',
                'loc.city',
                'loc.roadName',
                'loc.fromHouseNumber',
                'ST_X(loc.fromPoint) as fromLongitude',
                'ST_Y(loc.fromPoint) as fromLatitude',
                'loc.toHouseNumber',
                'ST_X(loc.toPoint) as toLongitude',
                'ST_Y(loc.toPoint) as toLatitude',
            )
            ->where('roc.status = :status')
            ->setParameter('status', RegulationOrderRecordStatusEnum::PUBLISHED)
            ->innerJoin('ro.regulationOrderRecord', 'roc')
            ->innerJoin('ro.regulationCondition', 'rc')
            ->innerJoin('rc.overallPeriod', 'o')
            ->innerJoin('rc.location', 'loc')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(RegulationOrder $regulationOrder): RegulationOrder
    {
        $this->getEntityManager()->persist($regulationOrder);

        return $regulationOrder;
    }
}
