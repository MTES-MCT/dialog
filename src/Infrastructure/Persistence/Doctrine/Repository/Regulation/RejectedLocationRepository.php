<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\RejectedLocation;
use App\Domain\Regulation\Repository\RejectedLocationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class RejectedLocationRepository extends ServiceEntityRepository implements RejectedLocationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RejectedLocation::class);
    }

    public function add(RejectedLocation $rejectedLocation): RejectedLocation
    {
        $this->getEntityManager()->persist($rejectedLocation);

        return $rejectedLocation;
    }

    public function deleteByRegulationOrderRecordUuid(string $regulationOrderRecordUuid): void
    {
        foreach ($this->findByRegulationOrderRecordUuid($regulationOrderRecordUuid) as $rejectedLocation) {
            $this->getEntityManager()->remove($rejectedLocation);
        }
    }

    public function findByRegulationOrderRecordUuid(string $regulationOrderRecordUuid): array
    {
        return $this->createQueryBuilder('rl')
            ->where('rl.regulationOrderRecord = :uuid')
            ->setParameter('uuid', $regulationOrderRecordUuid)
            ->orderBy('rl.measureIndex', 'ASC')
            ->addOrderBy('rl.locationIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByOrganizationUuid(string $organizationUuid): array
    {
        return $this->createQueryBuilder('rl')
            ->addSelect('roc', 'ro')
            ->innerJoin('rl.regulationOrderRecord', 'roc')
            ->innerJoin('roc.regulationOrder', 'ro')
            ->where('roc.organization = :organizationUuid')
            ->setParameter('organizationUuid', $organizationUuid)
            ->orderBy('rl.createdAt', 'DESC')
            ->addOrderBy('ro.identifier', 'ASC')
            ->addOrderBy('rl.measureIndex', 'ASC')
            ->addOrderBy('rl.locationIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
