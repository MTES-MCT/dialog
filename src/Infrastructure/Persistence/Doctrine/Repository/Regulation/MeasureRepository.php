<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Regulation;

use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MeasureRepository extends ServiceEntityRepository implements MeasureRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Measure::class);
    }

    public function findOneByUuid(string $uuid): ?Measure
    {
        return $this->createQueryBuilder('m')
            ->where('m.uuid = :uuid')
            ->addSelect('p', 'l')
            ->setParameter('uuid', $uuid)
            ->leftJoin('m.periods', 'p')
            ->leftJoin('m.locations', 'l')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function add(Measure $measure): Measure
    {
        $this->getEntityManager()->persist($measure);

        return $measure;
    }

    public function delete(Measure $measure): void
    {
        $em = $this->getEntityManager();
        $em->remove($measure);

        // Locations, periods and vehicle set still hold a reference to the just-deleted Measure object.
        // Their rows are removed by the database (ON DELETE CASCADE) ; detach them to prevent
        // Doctrine from complaining about managed entities referencing a removed one in later flushes.

        foreach ($measure->getLocations() as $location) {
            if ($ns = $location->getNamedStreet()) {
                $em->detach($ns);
            }

            if ($nr = $location->getNumberedRoad()) {
                $em->detach($nr);
            }

            if ($rg = $location->getRawGeoJSON()) {
                $em->detach($rg);
            }

            $em->detach($location);
        }

        foreach ($measure->getPeriods() as $period) {
            if ($dailyRange = $period->getDailyRange()) {
                $em->detach($dailyRange);
            }

            foreach ($period->getTimeSlots() as $timeSlot) {
                $em->detach($timeSlot);
            }

            $em->detach($period);
        }

        if ($vehicleSet = $measure->getVehicleSet()) {
            $em->detach($vehicleSet);
        }
    }

    public function findByRegulationOrderRecordUuid(string $uuid): array
    {
        return $this->createQueryBuilder('m')
            ->addSelect('p', 't', 'd', 'v', 'l', 'ns', 'nr', 'rg', 'sa')
            ->innerJoin('m.regulationOrder', 'ro')
            ->innerJoin('ro.regulationOrderRecord', 'roc')
            ->leftJoin('m.locations', 'l')
            ->leftJoin('l.namedStreet', 'ns')
            ->leftJoin('l.numberedRoad', 'nr')
            ->leftJoin('l.rawGeoJSON', 'rg')
            ->leftJoin('l.storageArea', 'sa')
            ->leftJoin('m.periods', 'p')
            ->leftJoin('p.timeSlots', 't')
            ->leftJoin('p.dailyRange', 'd')
            ->leftJoin('m.vehicleSet', 'v')
            ->where('roc.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getResult()
        ;
    }
}
