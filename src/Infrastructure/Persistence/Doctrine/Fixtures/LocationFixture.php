<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class LocationFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $location1 = new Location(
            '51449b82-5032-43c8-a427-46b9ddb44762',
            $this->getReference('regulationOrder'),
            'Route du Grand Brossais 44260 Savenay',
            '15',
            'POINT(-1.935836 47.347024)',
            '37bis',
            'POINT(-1.930973 47.347917)',
        );

        $location2 = new Location(
            '2d79e1ff-c991-4767-b8c0-36b644038d0f',
            $this->getReference('regulationOrder2'),
            'Avenue de Fonneuve 82000 Montauban',
            '695',
            'POINT(1.362275 44.028996)',
            '253',
            'POINT(1.35931 44.025665)',
        );

        $location3 = new Location(
            'f15ed802-fa9b-4d75-ab04-d62ea46597e9',
            $this->getReference('regulationOrder3'),
            '75018 Paris 18e Arrondissement',
            null,
            null,
            null,
            null,
        );

        $manager->persist($location1);
        $manager->persist($location2);
        $manager->persist($location3);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderFixture::class,
        ];
    }
}
