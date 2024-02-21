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
    public const UUID_TYPICAL = 'e48cbfff-bb04-428e-9cb0-22456fd7aab6';
    public const UUID_DOES_NOT_EXIST = '3f45e08a-c6b6-4026-86f1-cb8766756ad5';
    public const UUID_PUBLISHED = '59143d8d-d201-4950-be76-f367e39be522';
    public const UUID_COMPLEX_VEHICLES = self::UUID_PUBLISHED;
    public const UUID_FULL_CITY = '0658c562-641f-75b5-8000-0acab688b2d7';
    public const UUID_PERMANENT_ONLY_ONE = 'fa8f07e7-2db6-444d-bb41-3815b46198be';

    public function load(ObjectManager $manager): void
    {
        $typicalMeasure = new Measure(
            self::UUID_TYPICAL,
            $this->getReference('typicalRegulationOrder'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2023-05-11'),
        );

        $typicalMeasureToRemove = new Measure(
            '0658d836-de22-75f2-8000-bb36c98113a5',
            $this->getReference('typicalRegulationOrder'),
            MeasureTypeEnum::SPEED_LIMITATION->value,
            new \DateTime('2023-05-11'),
            maxSpeed: 50,
        );

        $publishedMeasure = new Measure(
            self::UUID_PUBLISHED,
            $this->getReference('publishedRegulationOrder'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2023-06-01'),
        );

        $permanentMeasure = new Measure(
            'fa8f07e7-2db6-444d-bb41-3815b46198be',
            $this->getReference('regulationOrderPermanent'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2023-05-12'),
        );

        $fullCityMeasure = new Measure(
            self::UUID_FULL_CITY,
            $this->getReference('fullCityRegulationOrder'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2023-05-12'),
        );

        $cifsMeasure = new Measure(
            '06548f88-56a9-70e2-8000-a06baed6a93d',
            $this->getReference('regulationOrderCifs'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2021-11-02'),
        );

        $manager->persist($typicalMeasure);
        $manager->persist($typicalMeasureToRemove);
        $manager->persist($publishedMeasure);
        $manager->persist($permanentMeasure);
        $manager->persist($fullCityMeasure);
        $manager->persist($cifsMeasure);

        $this->addReference('typicalMeasure', $typicalMeasure);
        $this->addReference('typicalMeasureToRemove', $typicalMeasureToRemove);
        $this->addReference('publishedMeasure', $publishedMeasure);
        $this->addReference('permanentMeasure', $permanentMeasure);
        $this->addReference('fullCityMeasure', $fullCityMeasure);
        $this->addReference('cifsMeasure', $cifsMeasure);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderFixture::class,
        ];
    }
}
