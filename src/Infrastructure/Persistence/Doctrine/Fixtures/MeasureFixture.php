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

        $manager->persist($typicalMeasure);
        $manager->persist($typicalMeasureToRemove);

        $this->addReference('typicalMeasure', $typicalMeasure);
        $this->addReference('typicalMeasureToRemove', $typicalMeasureToRemove);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderFixture::class,
        ];
    }
}
