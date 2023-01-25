<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Condition\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class LocationFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $location = new Location(
            '51449b82-5032-43c8-a427-46b9ddb44762',
            $this->getReference('regulationCondition1'),
            '44260',
            'Savenay',
            'Route du Grand Brossais',
            '15',
            'POINT(-1.935836 47.347024)',
            '37bis',
            'POINT(-1.930973 47.347917)',
        );

        $manager->persist($location);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationConditionFixture::class,
        ];
    }
}
