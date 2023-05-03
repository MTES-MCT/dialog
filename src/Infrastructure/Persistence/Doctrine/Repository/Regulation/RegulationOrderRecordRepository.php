<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
        bool $isPermanent,
    ): array {
        $query = $this->createQueryBuilder('roc')
            ->where('roc.organization = :organization')
            ->setParameter('organization', $organization->getUuid())
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
                'ro.identifier',
                'org.uuid as organizationUuid',
                'org.name as organizationName',
                'roc.status',
                'ro.description',
                'ro.startDate',
                'ro.endDate',
                'l.uuid as locationUuid',
                'l.address',
                'l.fromHouseNumber',
                'l.toHouseNumber',
                'm.type',
            )
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->innerJoin('roc.organization', 'org')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->leftJoin('ro.locations', 'l')
            ->leftJoin('l.measures', 'm')
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
                'loc.address',
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

    public function add(RegulationOrderRecord $regulationOrderRecord): RegulationOrderRecord
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
