<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

final class RegulationOrderRecordRepository extends ServiceEntityRepository implements RegulationOrderRecordRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private string $dialogOrgId,
    ) {
        parent::__construct($registry, RegulationOrderRecord::class);
    }

    public function findRegulationsByOrganizations(
        array $organizationUuids,
        int $maxItemsPerPage,
        int $page,
        bool $isPermanent,
    ): array {
        $query = $this->createQueryBuilder('roc')
            ->where('roc.organization IN (:organizationUuids)')
            ->setParameter('organizationUuids', $organizationUuids)
            ->innerJoin('roc.organization', 'o')
            ->innerJoin('roc.regulationOrder', 'ro', 'WITH', $isPermanent ? 'ro.endDate IS NULL' : 'ro.endDate IS NOT NULL')
            ->leftJoin('ro.locations', 'loc')
            ->orderBy('ro.startDate', 'DESC')
            ->addGroupBy('ro, roc')
            ->setFirstResult($maxItemsPerPage * ($page - 1))
            ->setMaxResults($maxItemsPerPage)
            ->getQuery();

        $paginator = new Paginator($query, false);
        $result = [
            'items' => [],
            'count' => \count($paginator),
        ];

        foreach ($paginator as $regulationOrderRecord) {
            array_push($result['items'], $regulationOrderRecord);
        }

        return $result;
    }

    public function findOneByUuid(string $uuid): RegulationOrderRecord|null
    {
        return $this->createQueryBuilder('roc')
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('roc.organization', 'o')
            ->leftJoin('ro.locations', 'l')
            ->leftJoin('l.measures', 'm')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneForSummary(string $uuid): ?RegulationOrderRecord
    {
        return $this->createQueryBuilder('roc')
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('roc.organization', 'org')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->leftJoin('ro.locations', 'l')
            ->leftJoin('l.measures', 'm')
            ->leftJoin('m.periods', 'p')
            ->leftJoin('m.vehicleSet', 'v')
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
                'loc.address',
                'loc.fromHouseNumber',
                'ST_X(loc.fromPoint) as fromLongitude',
                'ST_Y(loc.fromPoint) as fromLatitude',
                'loc.toHouseNumber',
                'ST_X(loc.toPoint) as toLongitude',
                'ST_Y(loc.toPoint) as toLatitude',
                'm.maxSpeed',
                'm.type',
                'v.restrictedTypes as restrictedVehicleTypes',
                'v.critairTypes as restrictedCritairTypes',
                'v.exemptedTypes as exemptedVehicleTypes',
                'v.heavyweightMaxWeight',
                'v.heavyweightMaxWidth',
                'v.heavyweightMaxLength',
                'v.heavyweightMaxHeight',
            )
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('roc.organization', 'o')
            ->innerJoin('ro.locations', 'loc')
            ->innerJoin('loc.measures', 'm')
            ->leftJoin('m.vehicleSet', 'v')
            ->where('roc.status = :status')
            ->setParameter('status', RegulationOrderRecordStatusEnum::PUBLISHED)
            ->orderBy('roc.uuid')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findRegulationOrdersForCifsIncidentFormat(): array
    {
        return $this->createQueryBuilder('roc')
            ->select(
                'roc.createdAt',
                'ro.description',
                'ro.category',
                'ro.startDate as regulationOrderStartDate',
                'ro.endDate as regulationOrderEndDate',
                'loc.address',
                'ST_X(loc.fromPoint) as fromLongitude',
                'ST_Y(loc.fromPoint) as fromLatitude',
                'ST_X(loc.toPoint) as toLongitude',
                'ST_Y(loc.toPoint) as toLatitude',
                'm.uuid as measureId',
                'm.type as measureType',
                'p.startDateTime as periodStartDateTime',
                'p.endDateTime as periodEndDateTime',
                'd.applicableDays',
                't.startTime',
                't.endTime',
            )
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('roc.organization', 'o')
            ->innerJoin('ro.locations', 'loc')
            ->innerJoin('loc.measures', 'm')
            ->leftJoin('m.periods', 'p')
            ->leftJoin('p.dailyRange', 'd')
            ->leftJoin('d.timeSlots', 't')
            ->where(
                'roc.status = :status',
                'ro.endDate IS NOT NULL',
                'm.type = :measureType',
                'loc.fromPoint IS NOT NULL',
                'loc.toPoint IS NOT NULL',
            )
            ->setParameter('status', RegulationOrderRecordStatusEnum::PUBLISHED)
            ->setParameter('measureType', MeasureTypeEnum::NO_ENTRY->value)
            ->orderBy('m.uuid')
            ->getQuery()
            ->getResult()
        ;
    }

    public function add(RegulationOrderRecord $regulationOrderRecord): RegulationOrderRecord
    {
        $this->getEntityManager()->persist($regulationOrderRecord);

        return $regulationOrderRecord;
    }

    public function doesOneExistInOrganizationWithIdentifier(
        Organization $organization,
        string $identifier,
    ): bool {
        $row = $this->createQueryBuilder('roc')
            ->select('roc.uuid')
            ->where('roc.organization = :organization')
            ->innerJoin('roc.regulationOrder', 'ro', 'WITH', 'ro.identifier = :identifier')
            ->setParameters([
                'identifier' => $identifier,
                'organization' => $organization,
            ])
            ->getQuery()
            ->getOneOrNullResult();

        return $row !== null;
    }

    public function findIdentifiersForSourceInOrganization(string $source, Organization $organization): array
    {
        return $this->createQueryBuilder('roc')
            ->select('ro.identifier')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->where('roc.source = :source')
            ->setParameter('source', $source)
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function countTotalRegulationOrderRecords(): int
    {
        return $this->createQueryBuilder('roc')
            ->select('count(DISTINCT(roc.uuid))')
            ->where('o.uuid <> :uuid')
            ->setParameter('uuid', $this->dialogOrgId)
            ->innerJoin('roc.organization', 'o')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPublishedRegulationOrderRecords(): int
    {
        return $this->createQueryBuilder('roc')
            ->select('count(DISTINCT(roc.uuid))')
            ->where('roc.status = :status')
            ->andWhere('o.uuid <> :uuid')
            ->setParameter('uuid', $this->dialogOrgId)
            ->innerJoin('roc.organization', 'o')
            ->setParameter('status', RegulationOrderRecordStatusEnum::PUBLISHED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPermanentRegulationOrderRecords(): int
    {
        return $this->createQueryBuilder('roc')
            ->select('count(DISTINCT(roc.uuid))')
            ->where('o.uuid <> :uuid')
            ->setParameter('uuid', $this->dialogOrgId)
            ->innerJoin('roc.organization', 'o')
            ->innerJoin('roc.regulationOrder', 'ro', 'WITH', 'ro.endDate IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTemporaryRegulationOrderRecords(): int
    {
        return $this->createQueryBuilder('roc')
            ->select('count(DISTINCT(roc.uuid))')
            ->where('o.uuid <> :uuid')
            ->setParameter('uuid', $this->dialogOrgId)
            ->innerJoin('roc.regulationOrder', 'ro', 'WITH', 'ro.endDate IS NOT NULL')
            ->innerJoin('roc.organization', 'o')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
