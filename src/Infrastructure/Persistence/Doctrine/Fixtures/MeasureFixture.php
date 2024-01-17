<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Measure;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class MeasureFixture extends Fixture implements DependentFixtureInterface
{
    public const INDEX_TYPICAL_TO_REMOVE = 1;

    public function load(ObjectManager $manager): void
    {
        $typicalMeasure = new Measure(
            'e48cbfff-bb04-428e-9cb0-22456fd7aab6',
            $this->getReference('typicalLocation'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2023-05-11'),
        );

        $typicalMeasureToRemove = new Measure(
            '0658d836-de22-75f2-8000-bb36c98113a5',
            $this->getReference('typicalLocation'),
            MeasureTypeEnum::SPEED_LIMITATION->value,
            new \DateTime('2023-05-11'),
            maxSpeed: 50,
        );

        $permanentRegulationOrderLocationMeasure1 = new Measure(
            'fa8f07e7-2db6-444d-bb41-3815b46198be',
            $this->getReference('permanentRegulationOrderLocation'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2023-05-12'),
        );

        $permanentRegulationOrderLocationMeasure2 = new Measure(
            'f0872729-a4da-453e-9cc7-af4d6e1fc53d',
            $this->getReference('permanentRegulationOrderLocation'),
            MeasureTypeEnum::ALTERNATE_ROAD->value,
            new \DateTime('2023-05-10'),
        );

        $publishedLocation1Measure1 = new Measure(
            '59143d8d-d201-4950-be76-f367e39be522',
            $this->getReference('publishedLocation1'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2023-06-01'),
        );

        $publishedLocation1Measure2 = new Measure(
            '064ca781-8f36-712a-8000-62bf928382bf',
            $this->getReference('publishedLocation1'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2023-06-01'),
        );

        $publishedLocation2NoEntry = new Measure(
            '064ca781-da33-7e29-8000-29380bad748e',
            $this->getReference('publishedLocation2'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2023-06-01'),
        );

        $publishedLocation2SpeedLimit = new Measure(
            '51ce5aee-50e2-4a31-997d-66064caf5267',
            $this->getReference('publishedLocation2'),
            MeasureTypeEnum::SPEED_LIMITATION->value,
            new \DateTime('2023-06-01'),
            maxSpeed: 50,
        );

        $publishedLocation3Measure = new Measure(
            '0655b3f8-b3e2-7061-8000-953e3d616153',
            $this->getReference('publishedLocation3'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2023-06-01'),
        );

        $manager->persist($typicalMeasure);
        $manager->persist($typicalMeasureToRemove);
        $manager->persist($permanentRegulationOrderLocationMeasure1);
        $manager->persist($permanentRegulationOrderLocationMeasure2);
        $manager->persist($publishedLocation1Measure1);
        $manager->persist($publishedLocation1Measure2);
        $manager->persist($publishedLocation2NoEntry);
        $manager->persist($publishedLocation2SpeedLimit);
        $manager->persist($publishedLocation3Measure);

        $this->addReference('typicalMeasure', $typicalMeasure);
        $this->addReference('typicalMeasureToRemove', $typicalMeasureToRemove);
        $this->addReference('permanentRegulationOrderLocationMeasure1', $permanentRegulationOrderLocationMeasure1);
        $this->addReference('permanentRegulationOrderLocationMeasure2', $permanentRegulationOrderLocationMeasure2);
        $this->addReference('publishedLocation1Measure1', $publishedLocation1Measure1);
        $this->addReference('publishedLocation1Measure2', $publishedLocation1Measure2);
        $this->addReference('publishedLocation2NoEntry', $publishedLocation2NoEntry);
        $this->addReference('publishedLocation2SpeedLimit', $publishedLocation2SpeedLimit);
        $this->addReference('publishedLocation3Measure', $publishedLocation3Measure);
        $this->addReference('measureWithComplexVehicleSet', $publishedLocation1Measure1);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LocationFixture::class,
        ];
    }
}
