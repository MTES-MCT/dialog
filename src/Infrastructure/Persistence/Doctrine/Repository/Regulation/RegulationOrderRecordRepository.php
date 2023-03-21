<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RegulationOrderRecordRepository extends ServiceEntityRepository implements RegulationOrderRecordRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegulationOrderRecord::class);
    }

    public function findRegulationsByOrganization(
        Organization $organization,
        int $maxItemsPerPage,
        int $page,
        string $status,
    ): array {
        return $this->createQueryBuilder('roc')
            ->select('roc.uuid, ro.startDate, ro.endDate, roc.status')
            ->where('roc.status = :status')
            ->andWhere('roc.organization = :organization')
            ->setParameters(['status' => $status, 'organization' => $organization->getUuid()])
            ->innerJoin('roc.regulationOrder', 'ro')
            ->orderBy('ro.startDate', 'DESC')
            ->setFirstResult($maxItemsPerPage * ($page - 1))
            ->setMaxResults($maxItemsPerPage)
            ->getQuery()
            ->getResult()
        ;
    }

    public function countRegulationsByOrganization(Organization $organization, string $status): int
    {
        return $this->createQueryBuilder('roc')
            ->select('count(roc.uuid)')
            ->where('roc.status = :status')
            ->andWhere('roc.organization = :organization')
            ->setParameters(['status' => $status, 'organization' => $organization->getUuid()])
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findOneByUuid(string $uuid): RegulationOrderRecord|null
    {
        return $this->createQueryBuilder('roc')
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('roc.organization', 'o')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneForSummary(string $uuid): array|null
    {
        return $this->createQueryBuilder('roc')
            ->select(
                'roc.uuid',
                'org.uuid as organizationUuid',
                'roc.status',
                'ro.description',
                'ro.startDate',
                'ro.endDate',
                'l.postalCode',
                'l.city',
                'l.roadName',
                'l.fromHouseNumber',
                'l.toHouseNumber',
            )
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('roc.organization', 'org')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->leftJoin('ro.locations', 'l')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findRegulationOrdersForDatexFormat(): array
    {
        return $this->createQueryBuilder('roc')
            ->select(
                'ro.uuid',
                'ro.issuingAuthority',
                'ro.description',
                'ro.startDate',
                'ro.endDate',
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
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('ro.locations', 'loc')
            ->where('roc.status = :status')
            ->setParameter('status', RegulationOrderRecordStatusEnum::PUBLISHED)
            ->setMaxResults(20)
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(RegulationOrderRecord $regulationOrderRecord): RegulationOrderRecord
    {
        $this->getEntityManager()->persist($regulationOrderRecord);

        return $regulationOrderRecord;
    }
}
