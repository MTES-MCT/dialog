<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Application\DateUtilsInterface;
use App\Application\Regulation\View\GeneralInfoView;
use App\Domain\Regulation\DTO\RegulationListFiltersDTO;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Enum\RegulationOrderTypeEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

final class RegulationOrderRecordRepository extends ServiceEntityRepository implements RegulationOrderRecordRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private DateUtilsInterface $dateUtils,
        private string $dialogOrgId,
    ) {
        parent::__construct($registry, RegulationOrderRecord::class);
    }

    private const OVERALL_START_DATE_QUERY_TEMPLATE = '
        SELECT MIN(_p%%n.startDateTime)
        FROM App\Domain\Condition\Period\Period _p%%n
        INNER JOIN _p%%n.measure _m%%n
        INNER JOIN _m%%n.regulationOrder _ro%%n
        WHERE _ro%%n.uuid = ro.uuid';

    private const OVERALL_END_DATE_QUERY_TEMPLATE = "
        SELECT MAX(_p%%n.endDateTime)
        FROM App\Domain\Condition\Period\Period _p%%n
        INNER JOIN _p%%n.measure _m%%n
        INNER JOIN _m%%n.regulationOrder _ro%%n
        WHERE _ro%%n.uuid = ro.uuid";

    private const COUNT_LOCATIONS_QUERY = '
        SELECT count(DISTINCT(_loc.uuid))
        FROM App\Domain\Regulation\Location\Location _loc
        INNER JOIN _loc.measure _m
        INNER JOIN _m.regulationOrder _ro
        WHERE _ro.uuid = ro.uuid';

    private const GET_NAMED_STREET_QUERY = "
        FIRST(
            SELECT CONCAT(_ns2.roadName, '#', _ns2.cityLabel, '#',  _ns2.cityCode)
            FROM App\Domain\Regulation\Location\Location _loc2
            INNER JOIN _loc2.namedStreet _ns2
            INNER JOIN _loc2.measure _m2
            INNER JOIN _m2.regulationOrder _ro2
            WHERE _ro2.uuid = ro.uuid
        )";

    private const GET_NUMBERED_ROAD_QUERY = "
            FIRST(
                SELECT CONCAT(_nr3.roadNumber, '#', _nr3.administrator)
                FROM App\Domain\Regulation\Location\Location _loc3
                INNER JOIN _loc3.numberedRoad _nr3
                INNER JOIN _loc3.measure _m3
                INNER JOIN _m3.regulationOrder _ro3
                WHERE _ro3.uuid = ro.uuid
            )";

    private const GET_RAW_GEOJSON_QUERY = "
        FIRST(
            SELECT _rg4.label
            FROM App\Domain\Regulation\Location\Location _loc4
            INNER JOIN _loc4.rawGeoJSON _rg4
            INNER JOIN _loc4.measure _m4
            INNER JOIN _m4.regulationOrder _ro4
            WHERE _ro4.uuid = ro.uuid
        )";

    public function findAllRegulations(
        RegulationListFiltersDTO $dto,
    ): array {
        $query = $this->createQueryBuilder('roc')
            ->select('roc.uuid, ro.identifier, ro.category, roc.status, o.name as organizationName, o.uuid as organizationUuid')
            ->addSelect(\sprintf('(%s) AS overallStartDate', str_replace('%%n', '10', self::OVERALL_START_DATE_QUERY_TEMPLATE)))
            ->addSelect(\sprintf('(%s) AS overallEndDate', str_replace('%%n', '11', self::OVERALL_END_DATE_QUERY_TEMPLATE)))
            ->addSelect(\sprintf('(%s) as nbLocations', self::COUNT_LOCATIONS_QUERY))
            ->addSelect(\sprintf('(%s) as namedStreet', self::GET_NAMED_STREET_QUERY))
            ->addSelect(\sprintf('(%s) as numberedRoad', self::GET_NUMBERED_ROAD_QUERY))
            ->addSelect(\sprintf('(%s) as rawGeoJSON', self::GET_RAW_GEOJSON_QUERY))
        ;

        $parameters = [];

        // Identifier filter: retrieve all regulation orders whose identifier contains the $identifier search term
        if ($dto->identifier) {
            $query
                // Doctrine doesn't have ILIKE support for Postgres, we use https://github.com/martin-georgiev/postgresql-for-doctrine
                ->andWhere('PG_ILIKE(ro.identifier, :identifierPattern) = TRUE');
            $parameters['identifierPattern'] = '%' . $dto->identifier . '%';
        }

        // Organization filter
        if ($dto->organizationUuid) {
            $query
                ->andWhere('roc.organization = :organizationUuid');
            $parameters['organizationUuid'] = $dto->organizationUuid;
        }

        // Regulation order type filter
        if ($dto->regulationOrderType === RegulationOrderTypeEnum::PERMANENT->value) {
            $query->andWhere('ro.category = :permanentCategory');
            $parameters['permanentCategory'] = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        } elseif ($dto->regulationOrderType === RegulationOrderTypeEnum::TEMPORARY->value) {
            $query->andWhere('ro.category <> :permanentCategory');
            $parameters['permanentCategory'] = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        }

        // Status filter
        if ($dto->status === RegulationOrderRecordStatusEnum::DRAFT->value) {
            $query
                ->andWhere('roc.status = :draft');
            $parameters['draft'] = RegulationOrderRecordStatusEnum::DRAFT->value;
        } elseif ($dto->status === RegulationOrderRecordStatusEnum::PUBLISHED->value) {
            $query
                ->andWhere('roc.status = :published');
            $parameters['published'] = RegulationOrderRecordStatusEnum::PUBLISHED->value;
        }

        $query->setParameters($parameters);

        $query
            ->innerJoin('roc.organization', 'o')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->orderBy('overallStartDate', 'DESC')
            ->addOrderBy('ro.identifier', 'ASC')
            ->addGroupBy('ro, roc, o')
            ->setFirstResult($dto->pageSize * ($dto->page - 1))
            ->setMaxResults($dto->pageSize)
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

    public function findOneByUuid(string $uuid): ?RegulationOrderRecord
    {
        return $this->createQueryBuilder('roc')
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('roc.organization', 'o')
            ->leftJoin('ro.measures', 'm')
            ->leftJoin('m.locations', 'l')
            ->leftJoin('l.numberedRoad', 'nr')
            ->leftJoin('l.namedStreet', 'ns')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneUuidByIdentifierInOrganization(string $identifier, Organization $organization): ?string
    {
        $row = $this->createQueryBuilder('roc')
            ->select('roc.uuid')
            ->where('roc.organization = :organization')
            ->innerJoin('roc.regulationOrder', 'ro', 'WITH', 'ro.identifier = :identifier')
            ->setParameters([
                'identifier' => $identifier,
                'organization' => $organization,
            ])
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $row ? $row['uuid'] : null;
    }

    /**
     * @return GeneralInfoView[]
     */
    public function findGeneralInformation(string $uuid): ?array
    {
        return $this->createQueryBuilder('roc')
            ->select(
                \sprintf(
                    '
                    roc.uuid,
                    ro.identifier,
                    org.name as organizationName,
                    org.uuid as organizationUuid,
                    org.logo as organizationLogo,
                    roc.status,
                    ro.uuid as regulationOrderUuid,
                    ro.category,
                    ro.otherCategoryText,
                    ro.description,
                    (%s) as overallStartDate,
                    (%s) as overallEndDate
                ',
                    str_replace('%%n', '10', self::OVERALL_START_DATE_QUERY_TEMPLATE),
                    str_replace('%%n', '11', self::OVERALL_END_DATE_QUERY_TEMPLATE),
                ),
            )
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('roc.organization', 'org')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOrganizationUuid(string $uuid): ?string
    {
        $row = $this->createQueryBuilder('roc')
            ->select('o.uuid')
            ->innerJoin('roc.organization', 'o')
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $row ? $row['uuid'] : null;
    }

    public function findRegulationOrdersForDatexFormat(): array
    {
        return $this->createQueryBuilder('roc')
            ->addSelect('ro', 'm', 'loc', 'v', 'p', 'd', 't', 'nr', 'ns', 'rg')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('roc.organization', 'o')
            ->innerJoin('ro.measures', 'm')
            ->innerJoin('m.locations', 'loc')
            ->leftJoin('loc.namedStreet', 'ns')
            ->leftJoin('loc.numberedRoad', 'nr')
            ->leftJoin('loc.rawGeoJSON', 'rg')
            ->leftJoin('m.vehicleSet', 'v')
            ->leftJoin('m.periods', 'p')
            ->leftJoin('p.dailyRange', 'd')
            ->leftJoin('p.timeSlots', 't')
            ->where('roc.status = :status')
            ->setParameters([
                'status' => RegulationOrderRecordStatusEnum::PUBLISHED->value,
            ])
            ->andWhere('loc.geometry IS NOT NULL')
            ->orderBy('roc.uuid')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getOverallDatesByRegulationUuids(array $uuids): array
    {
        $rows = $this->createQueryBuilder('roc')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->select('roc.uuid, ro.category')
            ->addSelect(\sprintf('(%s) AS overallStartDate', str_replace('%%n', '10', self::OVERALL_START_DATE_QUERY_TEMPLATE)))
            ->addSelect(\sprintf('(%s) AS overallEndDate', str_replace('%%n', '11', self::OVERALL_END_DATE_QUERY_TEMPLATE)))
            ->where('roc.uuid IN (:uuids)')
            ->setParameter('uuids', $uuids, ArrayParameterType::STRING)
            ->getQuery()
            ->getResult()
        ;

        // Return array of $uuid => {overallStartDate, overallEndDate}
        $result = [];

        foreach ($rows as $row) {
            $startDate = $row['overallStartDate'] ? new \DateTimeImmutable($row['overallStartDate']) : null;
            $endDate = null;

            if ($row['overallEndDate'] && $row['category'] !== RegulationOrderCategoryEnum::PERMANENT_REGULATION->value) {
                $endDate = new \DateTimeImmutable($row['overallEndDate']);
            }

            $result[$row['uuid']] = [
                'uuid' => $row['uuid'],
                'overallStartDate' => $startDate,
                'overallEndDate' => $endDate,
            ];
        }

        return $result;
    }

    public function findRegulationOrdersForCifsIncidentFormat(
        array $allowedSources = [],
        array $excludedIdentifiers = [],
        array $allowedLocationIds = [],
    ): array {
        return $this->createQueryBuilder('roc')
            ->addSelect('ro', 'loc', 'm', 'p', 'd', 't')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('ro.measures', 'm')
            ->innerJoin('m.locations', 'loc')
            ->leftJoin('m.vehicleSet', 'v')
            ->leftJoin('m.periods', 'p')
            ->leftJoin('p.dailyRange', 'd')
            ->leftJoin('p.timeSlots', 't')
            ->where(
                'roc.status = :status',
                \sprintf('(%s) >= :today', str_replace('%%n', '10', self::OVERALL_END_DATE_QUERY_TEMPLATE)),
                'loc.geometry IS NOT NULL',
                'loc.roadType NOT IN (:excludedRoadTypes) OR (loc.roadType = :rawGeoJSONRoadType AND roc.source = :litteralisSource)',
                $allowedSources ? 'roc.source in (:allowedSources)' : null,
                $excludedIdentifiers ? 'ro.identifier NOT IN (:excludedIdentifiers)' : null,
                $allowedLocationIds ? 'loc.uuid IN (:allowedLocationIds)' : null,
                'm.type = :measureType',
                'v IS NULL or (v.restrictedTypes = \'a:0:{}\' AND v.exemptedTypes = \'a:0:{}\')',
            )
            ->setParameters([
                ...($allowedSources ? ['allowedSources' => $allowedSources] : []),
                ...($excludedIdentifiers ? ['excludedIdentifiers' => $excludedIdentifiers] : []),
                ...($allowedLocationIds ? ['allowedLocationIds' => $allowedLocationIds] : []),
                'status' => RegulationOrderRecordStatusEnum::PUBLISHED->value,
                'measureType' => MeasureTypeEnum::NO_ENTRY->value,
                'today' => $this->dateUtils->getNow(),
                'excludedRoadTypes' => [RoadTypeEnum::RAW_GEOJSON->value],
                // Allow RawGeoJSON locations only for Litteralis source
                'rawGeoJSONRoadType' => RoadTypeEnum::RAW_GEOJSON->value,
                'litteralisSource' => RegulationOrderRecordSourceEnum::LITTERALIS->value,
            ])
            ->orderBy('loc.uuid') // Predictable order
            ->getQuery()
            ->getResult()
        ;
    }

    public function findRegulationOrdersForLitteralisCleanUp(string $organizationId, \DateTimeInterface $laterThan): array
    {
        return $this->createQueryBuilder('roc')
            ->addSelect('ro')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->where(
                'roc.source = :source',
                'roc.organization = :organizationId',
                'roc.status = :status',
                \sprintf('ro.category = :permanentCategory OR (%s) >= :laterThan', str_replace('%%n', '10', self::OVERALL_END_DATE_QUERY_TEMPLATE)),
            )
            ->setParameters([
                'source' => RegulationOrderRecordSourceEnum::LITTERALIS->value,
                'organizationId' => $organizationId,
                'status' => RegulationOrderRecordStatusEnum::PUBLISHED->value,
                'laterThan' => $laterThan,
                'permanentCategory' => RegulationOrderCategoryEnum::PERMANENT_REGULATION->value,
            ])
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
        return $this->findOneUuidByIdentifierInOrganization($identifier, $organization) !== null;
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
            ->setParameter('status', RegulationOrderRecordStatusEnum::PUBLISHED->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPermanentRegulationOrderRecords(): int
    {
        return $this->createQueryBuilder('roc')
            ->select('count(DISTINCT(roc.uuid))')
            ->innerJoin('roc.organization', 'o')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->where(
                'o.uuid <> :uuid',
                'ro.category = :permanentCategory',
            )
            ->setParameters([
                'uuid' => $this->dialogOrgId,
                'permanentCategory' => RegulationOrderCategoryEnum::PERMANENT_REGULATION->value,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTemporaryRegulationOrderRecords(): int
    {
        return $this->createQueryBuilder('roc')
            ->select('count(DISTINCT(roc.uuid))')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->innerJoin('roc.organization', 'o')
            ->where(
                'o.uuid <> :uuid',
                'ro.category <> :permanentCategory',
            )
            ->setParameters([
                'uuid' => $this->dialogOrgId,
                'permanentCategory' => RegulationOrderCategoryEnum::PERMANENT_REGULATION->value,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
