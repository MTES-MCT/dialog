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
    public function load(ObjectManager $manager): void
    {
        $measure1 = new Measure(
            'fa8f07e7-2db6-444d-bb41-3815b46198be',
            $this->getReference('location3'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2023-05-12'),
        );

        $measure2 = new Measure(
            'f0872729-a4da-453e-9cc7-af4d6e1fc53d',
            $this->getReference('location3'),
            MeasureTypeEnum::ALTERNATE_ROAD->value,
            new \DateTime('2023-05-10'),
        );

        $measure3 = new Measure(
            'e48cbfff-bb04-428e-9cb0-22456fd7aab6',
            $this->getReference('location1'),
            MeasureTypeEnum::SPEED_LIMITATION->value,
            new \DateTime('2023-05-11'),
            50,
        );

        $measure4 = new Measure(
            '59143d8d-d201-4950-be76-f367e39be522',
            $this->getReference('location2'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2023-06-01'),
        );

        $measure5 = new Measure(
            '064ca781-8f36-712a-8000-62bf928382bf',
            $this->getReference('location2'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2023-06-01'),
        );

        $measure6 = new Measure(
            '064ca781-da33-7e29-8000-29380bad748e',
            $this->getReference('location2Bis'),
            MeasureTypeEnum::NO_ENTRY->value,
            new \DateTime('2023-06-01'),
        );

        $manager->persist($measure1);
        $manager->persist($measure2);
        $manager->persist($measure3);
        $manager->persist($measure4);
        $manager->persist($measure5);
        $manager->persist($measure6);

        $this->addReference('measure1', $measure1);
        $this->addReference('measure3', $measure3);
        $this->addReference('measure4', $measure4);
        $this->addReference('measure5', $measure5);
        $this->addReference('measure6', $measure6);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LocationFixture::class,
        ];
    }
}
