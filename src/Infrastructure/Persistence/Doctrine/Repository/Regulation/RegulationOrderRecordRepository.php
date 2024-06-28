<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Application\DateUtilsInterface;
use App\Application\Regulation\View\GeneralInfoView;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
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
        private DateUtilsInterface $dateUtils,
        private string $dialogOrgId,
    ) {
        parent::__construct($registry, RegulationOrderRecord::class);
    }

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

    public function findAllRegulations(
        int $maxItemsPerPage,
        int $page,
        bool $isPermanent,
        ?array $organizationUuids = null,
    ): array {
        $query = $this->createQueryBuilder('roc')
            ->select('roc.uuid, ro.identifier, roc.status, o.name as organizationName, o.uuid as organizationUuid, ro.startDate, ro.endDate')
            ->addSelect(sprintf('(%s) as nbLocations', self::COUNT_LOCATIONS_QUERY))
            ->addSelect(sprintf('(%s) as namedStreet', self::GET_NAMED_STREET_QUERY))
            ->addSelect(sprintf('(%s) as numberedRoad', self::GET_NUMBERED_ROAD_QUERY));

        if ($organizationUuids) {
            $query
                ->where('(roc.status = :published) OR (roc.status = :draft AND roc.organization IN (:organizationUuids))')
                ->setParameters([
                    'organizationUuids' => $organizationUuids,
                    'published' => RegulationOrderRecordStatusEnum::PUBLISHED,
                    'draft' => RegulationOrderRecordStatusEnum::DRAFT,
                ]);
        } else {  // the user is not connected -> no draft regulations
            $query
                ->where('roc.status = :published')
                ->setParameters([
                    'published' => RegulationOrderRecordStatusEnum::PUBLISHED,
                ]);
        }

        $query
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

    /**
     * @return GeneralInfoView[]
     */
    public function findGeneralInformation(string $uuid): ?array
    {
        return $this->createQueryBuilder('roc')
            ->select(
                sprintf(
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
                ),
            )
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('roc.organization', 'org')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->getQuery()
            ->getResult()
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
                'status' => RegulationOrderRecordStatusEnum::PUBLISHED,
            ])
            ->andWhere('loc.geometry IS NOT NULL')
            ->orderBy('roc.uuid')
            ->getQuery()
            ->getResult()
        ;
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
                'ro.endDate >= :today',
                'loc.geometry IS NOT NULL',
                'loc.roadType NOT IN (:excludedRoadTypes)',
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
                'status' => RegulationOrderRecordStatusEnum::PUBLISHED,
                'measureType' => MeasureTypeEnum::NO_ENTRY->value,
                'today' => $this->dateUtils->getNow(),
                'excludedRoadTypes' => [RoadTypeEnum::RAW_GEOJSON->value],
            ])
            ->orderBy('loc.uuid') // Predictable order
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
