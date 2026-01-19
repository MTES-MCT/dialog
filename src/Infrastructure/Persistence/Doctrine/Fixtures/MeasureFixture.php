<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class MeasureFixture extends Fixture implements DependentFixtureInterface
{
    public const INDEX_TYPICAL_TO_REMOVE = 1;
    public const UUID_TYPICAL = 'e48cbfff-bb04-428e-9cb0-22456fd7aab6';
    public const UUID_DOES_NOT_EXIST = '3f45e08a-c6b6-4026-86f1-cb8766756ad5';
    public const UUID_PUBLISHED = '59143d8d-d201-4950-be76-f367e39be522';
    public const UUID_COMPLEX_VEHICLES = self::UUID_PUBLISHED;
    public const UUID_FULL_CITY = '0658c562-641f-75b5-8000-0acab688b2d7';
    public const UUID_PERMANENT_ONLY_ONE = 'fa8f07e7-2db6-444d-bb41-3815b46198be';
    public const UUID_CIFS = '06548f88-56a9-70e2-8000-a06baed6a93d';
    public const UUID_RAWGEOJSON = '06672e2d-669d-7593-8000-7cfd59230dc2';
    public const UUID_WINTER_MAINTENANCE = '5f19f73e-58ae-4281-bd17-362b8db003b7';
    public const UUID_PARKING_PROHIBITED = '3260382e-1d37-48f0-a5a1-9ced1d8192f2';

    public function load(ObjectManager $manager): void
    {
        $typicalMeasure = new Measure(
            self::UUID_TYPICAL,
            $this->getReference('typicalRegulationOrder', RegulationOrder::class),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTimeImmutable('2023-05-11'),
        );

        $typicalMeasureToRemove = new Measure(
            '0658d836-de22-75f2-8000-bb36c98113a5',
            $this->getReference('typicalRegulationOrder', RegulationOrder::class),
            MeasureTypeEnum::SPEED_LIMITATION->value,
            new \DateTimeImmutable('2023-05-11'),
            maxSpeed: 50,
        );

        $publishedMeasure = new Measure(
            self::UUID_PUBLISHED,
            $this->getReference('publishedRegulationOrder', RegulationOrder::class),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTimeImmutable('2023-06-01'),
        );

        $permanentMeasure = new Measure(
            'fa8f07e7-2db6-444d-bb41-3815b46198be',
            $this->getReference('regulationOrderPermanent', RegulationOrder::class),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTimeImmutable('2023-05-12'),
        );

        $fullCityMeasure = new Measure(
            self::UUID_FULL_CITY,
            $this->getReference('fullCityRegulationOrder', RegulationOrder::class),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTimeImmutable('2023-05-12'),
        );

        $cifsMeasure = new Measure(
            self::UUID_CIFS,
            $this->getReference('regulationOrderCifs', RegulationOrder::class),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTimeImmutable('2023-09-06'),
        );

        $outDatedCifsMeasure = new Measure(
            'd4823170-e47d-4a83-80c3-67078554651c',
            $this->getReference('outDatedRegulationOrderCifs', RegulationOrder::class),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTimeImmutable('2021-11-02'),
        );

        $rawGeoJSONMeasure = new Measure(
            self::UUID_RAWGEOJSON,
            $this->getReference('rawGeoJSONRegulationOrder', RegulationOrder::class),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTimeImmutable('2023-01-06'),
        );

        $litteralisMeasure = new Measure(
            '066e984c-9939-76ed-8000-d070d574f378',
            $this->getReference('litteralisRegulationOrder', RegulationOrder::class),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTimeImmutable('2023-06-01'),
        );

        $winterMaintenanceMeasure = new Measure(
            self::UUID_WINTER_MAINTENANCE,
            $this->getReference('winterMaintenanceRegulationOrder', RegulationOrder::class),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTimeImmutable('2025-01-15'),
        );

        $parkingProhibitedMeasure = new Measure(
            self::UUID_PARKING_PROHIBITED,
            $this->getReference('parkingProhibitedRegulationOrder', RegulationOrder::class),
            MeasureTypeEnum::PARKING_PROHIBITED->value,
            new \DateTimeImmutable('2025-01-15'),
        );

        $manager->persist($typicalMeasure);
        $manager->persist($typicalMeasureToRemove);
        $manager->persist($publishedMeasure);
        $manager->persist($permanentMeasure);
        $manager->persist($fullCityMeasure);
        $manager->persist($cifsMeasure);
        $manager->persist($outDatedCifsMeasure);
        $manager->persist($rawGeoJSONMeasure);
        $manager->persist($litteralisMeasure);
        $manager->persist($winterMaintenanceMeasure);
        $manager->persist($parkingProhibitedMeasure);

        $this->addReference('typicalMeasure', $typicalMeasure);
        $this->addReference('typicalMeasureToRemove', $typicalMeasureToRemove);
        $this->addReference('publishedMeasure', $publishedMeasure);
        $this->addReference('permanentMeasure', $permanentMeasure);
        $this->addReference('fullCityMeasure', $fullCityMeasure);
        $this->addReference('cifsMeasure', $cifsMeasure);
        $this->addReference('outDatedCifsMeasure', $outDatedCifsMeasure);
        $this->addReference('rawGeoJSONMeasure', $rawGeoJSONMeasure);
        $this->addReference('litteralisMeasure', $litteralisMeasure);
        $this->addReference('winterMaintenanceMeasure', $winterMaintenanceMeasure);
        $this->addReference('parkingProhibitedMeasure', $parkingProhibitedMeasure);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderFixture::class,
        ];
    }
}
