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
        bool $permanent,
    ): array {
        return $this->createQueryBuilder('roc')
            ->select('roc.uuid, ro.identifier, loc.city, loc.roadName, ro.startDate, ro.endDate, roc.status')
            ->where('roc.organization = :organization')
            ->setParameters(['organization' => $organization->getUuid()])
            ->andWhere($permanent ? 'ro.endDate IS NULL' : 'ro.endDate IS NOT NULL')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->leftJoin('ro.locations', 'loc')
            ->orderBy('ro.startDate', 'DESC')
            ->setFirstResult($maxItemsPerPage * ($page - 1))
            ->setMaxResults($maxItemsPerPage)
            ->getQuery()
            ->getResult()
        ;
    }

    public function countRegulationsByOrganization(Organization $organization, bool $permanent): int
    {
        return $this->createQueryBuilder('roc')
            ->select('count(roc.uuid)')
            ->where('roc.organization = :organization')
            ->setParameters(['organization' => $organization->getUuid()])
            ->andWhere($permanent ? 'ro.endDate IS NULL' : 'ro.endDate IS NOT NULL')
            ->innerJoin('roc.regulationOrder', 'ro')
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
                'o.name as organizationName',
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
            ->innerJoin('roc.organization', 'o')
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

    public function findOneByOrganizationAndIdentifier(
        Organization $organization,
        string $identifier,
    ): ?RegulationOrderRecord {
        return $this->createQueryBuilder('roc')
            ->select('partial roc.{uuid}')
            ->where('roc.organization = :organization')
            ->innerJoin('roc.regulationOrder', 'ro', 'WITH', 'ro.identifier = :identifier')
            ->setParameters([
                'identifier' => $identifier,
                'organization' => $organization,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}
