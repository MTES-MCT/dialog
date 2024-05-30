<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Application\DateUtilsInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class LocationRepository extends ServiceEntityRepository implements LocationRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private DateUtilsInterface $dateUtils,
    ) {
        parent::__construct($registry, Location::class);
    }

    public function add(Location $location): Location
    {
        $this->getEntityManager()->persist($location);

        return $location;
    }

    public function delete(Location $location): void
    {
        $this->getEntityManager()->remove($location);
    }

    public function findOneByUuid(string $uuid): ?Location
    {
        return $this->createQueryBuilder('l')
            ->addSelect('m')
            ->where('l.uuid = :uuid')
            ->innerJoin('l.measure', 'm')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAllForMapAsGeoJSON(
        bool $permanentRegulationsOnly = false,
        bool $temporaryRegulationsOnly = false,
        bool $includeUpcomingRegulations = false,
        bool $includePastRegulations = false,
    ): string {
        $query = $this->createQueryBuilder('l')
               ->select('l.geometry, m.type, l.uuid')
               ->innerJoin('l.measure', 'm')
               ->innerJoin('m.regulationOrder', 'ro')
               ->innerJoin('ro.regulationOrderRecord', 'roc')
               ->where('roc.status = :status')
               ->setParameter('status', RegulationOrderRecordStatusEnum::PUBLISHED);

        if ($permanentRegulationsOnly && !$temporaryRegulationsOnly) {
            $query->andWhere('ro.endDate IS NULL');
        } elseif (!$permanentRegulationsOnly && $temporaryRegulationsOnly) {
            $query->andWhere('ro.endDate IS NOT NULL');
        } elseif ($permanentRegulationsOnly && $temporaryRegulationsOnly) {
            return json_encode([]); // we return no regulations
        }
        // no filter needed for (!$permanentRegulationsOnly && !$temporaryRegulationsOnly) : we want all kind of regulations

        if (!$includeUpcomingRegulations && !$includePastRegulations) {
            $query->andWhere('(ro.endDate >= :now OR ro.endDate IS NULL) AND ro.startDate <= :now');
        } elseif ($includeUpcomingRegulations && !$includePastRegulations) {
            $query->andWhere('((ro.endDate >= :now OR ro.endDate IS NULL) AND ro.startDate <= :now) OR ro.startDate > :now');
        } elseif (!$includeUpcomingRegulations && $includePastRegulations) {
            $query->andWhere('((ro.endDate >= :now OR ro.endDate IS NULL) AND ro.startDate <= :now) OR ro.endDate < :now');
        }
        // no filter needed for ($includeUpcomingRegulations && $includePastRegulations) : we want all kind of regulations
        if (!($includeUpcomingRegulations && $includePastRegulations)) {
            $query->setParameter('now', $this->dateUtils->getNow()->format('Y-m-d'));
        }

        $geoJSONs = [];
        $results = $query->getQuery()->getResult();
        foreach ($results as $result) {
            $geoJSONs[] = [
                'type' => 'Feature',
                'geometry' => json_decode($result['geometry']),
                'properties' => [
                    'location_uuid' => $result['uuid'],
                    'measure_type' => $result['type'],
                ],
            ];
        }

        return json_encode([
            'type' => 'FeatureCollection',
            'features' => $geoJSONs,
        ]);
    }
}
