<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\LocationNew;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class LocationNewFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $datasets = [
            ['065a677c-e4f5-70a7-8000-477d9bbc4fcf', $this->getReference('typicalMeasure')],
            ['065a677d-2dae-71bc-8000-6753af1b9a4c', $this->getReference('typicalMeasureToRemove')],
            ['065a677d-5c28-7e2a-8000-aecf8c709533', $this->getReference('permanentRegulationOrderLocationMeasure1')],
            ['065a677d-8684-7dd5-8000-ca227a5edf0f', $this->getReference('permanentRegulationOrderLocationMeasure2')],
            ['065a677d-d1f6-7fc5-8000-4dce0597dda9', $this->getReference('publishedLocation1Measure1')],
            ['065a677d-fbd8-7c4b-8000-7683102fc26b', $this->getReference('publishedLocation1Measure2')],
            ['065a677e-22d9-7555-8000-8a60576f18ce', $this->getReference('publishedLocation2NoEntry')],
            ['065a677e-4aae-7f89-8000-9da7a148530e', $this->getReference('publishedLocation2SpeedLimit')],
            ['065a677e-7839-710f-8000-cc4df666c443', $this->getReference('publishedLocation3Measure')],
        ];

        foreach ($datasets as [$uuid, $measure]) {
            $manager->persist(LocationNew::fromLocation($uuid, $measure, $measure->getLocation()));
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MeasureFixture::class,
        ];
    }
}
