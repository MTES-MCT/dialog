<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Application\Regulation\View\GeneralInfoView;
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

    private const COUNT_LOCATIONS_QUERY = '
        SELECT count(DISTINCT(_locNew.uuid))
        FROM App\Domain\Regulation\Location _locNew
        INNER JOIN _locNew.measure _m
        INNER JOIN _m.regulationOrder _ro
        WHERE _ro.uuid = ro.uuid';

    private const GET_LOCATION_QUERY = "
        FIRST(
            SELECT CONCAT(_locNew2.roadName, '#', _locNew2.cityLabel, '#',  _locNew2.cityCode)
            FROM App\Domain\Regulation\Location _locNew2
            INNER JOIN _locNew2.measure _m2
            INNER JOIN _m2.regulationOrder _ro2
            WHERE _ro2.uuid = ro.uuid
        )";

    public function findRegulationsByOrganizations(
        array $organizationUuids,
        int $maxItemsPerPage,
        int $page,
        bool $isPermanent,
    ): array {
        $query = $this->createQueryBuilder('roc')
            ->select('roc.uuid, ro.identifier, roc.status, o.name as organizationName, ro.startDate, ro.endDate')
            ->addSelect(sprintf('(%s) as nbLocations', self::COUNT_LOCATIONS_QUERY))
            ->addSelect(sprintf('(%s) as location', self::GET_LOCATION_QUERY))
            ->where('roc.organization IN (:organizationUuids)')
            ->setParameter('organizationUuids', $organizationUuids)
            ->innerJoin('roc.organization', 'o')
            ->innerJoin('roc.regulationOrder', 'ro', 'WITH', $isPermanent ? 'ro.endDate IS NULL' : 'ro.endDate IS NOT NULL')
            ->orderBy('ro.startDate', 'DESC')
            ->addOrderBy('ro.identifier', 'ASC')
            ->addGroupBy('ro, roc, o')
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
            ->leftJoin('ro.measures', 'm')
            ->leftJoin('m.locations', 'l')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return GeneralInfoView[]
     */
    public function findGeneralInformation(string $uuid): ?array
    {
        return $this->createQueryBuilder('roc')
            ->select(sprintf(
                'NEW %s(
                    roc.uuid,
                    ro.identifier,
                    org.name,
                    org.uuid,
                    roc.status,
                    ro.category,
                    ro.otherCategoryText,
                    ro.description,
                    ro.startDate,
                    ro.endDate
                )',
                GeneralInfoView::class,
            ))
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('roc.organization', 'org')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->getQuery()
            ->getResult()
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
                'locNew.roadName',
                'ST_AsGeoJSON(locNew.geometry) as geometry',
                'm.maxSpeed',
                'm.type',
                'v.restrictedTypes as restrictedVehicleTypes',
                'v.critairTypes as restrictedCritairTypes',
                'v.exemptedTypes as exemptedVehicleTypes',
                'v.heavyweightMaxWeight',
                'v.maxWidth',
                'v.maxLength',
                'v.maxHeight',
            )
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('roc.organization', 'o')
            ->innerJoin('ro.measures', 'm')
            ->innerJoin('m.locations', 'locNew')
            ->leftJoin('m.vehicleSet', 'v')
            ->where('roc.status = :status')
            ->setParameter('status', RegulationOrderRecordStatusEnum::PUBLISHED)
            ->andWhere('locNew.geometry IS NOT NULL')
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
                'locNew.roadName',
                'ST_AsGeoJSON(locNew.geometry) as geometry',
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
            ->innerJoin('ro.measures', 'm')
            ->innerJoin('m.locations', 'locNew')
            ->leftJoin('m.vehicleSet', 'v')
            ->leftJoin('m.periods', 'p')
            ->leftJoin('p.dailyRange', 'd')
            ->leftJoin('p.timeSlots', 't')
            ->where(
                'roc.status = :status',
                'ro.endDate IS NOT NULL',
                'locNew.geometry IS NOT NULL',
                'm.type = :measureType',
                'v IS NULL or (v.restrictedTypes = \'a:0:{}\' AND v.exemptedTypes = \'a:0:{}\')',
            )
            ->setParameters([
                'status' => RegulationOrderRecordStatusEnum::PUBLISHED,
                'measureType' => MeasureTypeEnum::NO_ENTRY->value,
            ])
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

    public function findIdentifiersForSource(string $source): array
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
