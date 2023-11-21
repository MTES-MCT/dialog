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

        $location1Bis = new Location(
            '34247125-38f4-4e69-b5d7-5516a577d149',
            $this->getReference('regulationOrder'),
            'Rue Victor Hugo 44260 Savenay',
            null,
            null,
            null,
            null,
        );

        $location1Ter = new Location(
            '0b5d0ddf-f7aa-4f0a-af12-1f654a505200',
            $this->getReference('regulationOrder'),
            'Route du Lac 44260 Savenay',
            null,
            null,
            null,
            null,
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

        $location2Bis = new Location(
            '064ca782-771c-783f-8000-e67473eabea6',
            $this->getReference('regulationOrder2'),
            "Rue de l'Hôtel de Ville 82000 Montauban",
            '30',
            'POINT(1.352126 44.016833)',
            '12',
            'POINT(1.353016 44.016402)',
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

        $locationNoMeasures = new Location(
            '06500383-ad31-7e57-8000-e080e2755bd4',
            $this->getReference('regulationOrderNoMeasures'),
            'Route du Grand Brossais 44260 Savenay',
            '15',
            'POINT(-1.935836 47.347024)',
            '37bis',
            'POINT(-1.930973 47.347917)',
        );

        $locationCifs = new Location(
            '06548f85-d545-7b45-8000-8a23c45850b3',
            $this->getReference('regulationOrderCifs'),
            'Rue de la République 82000 Montauban',
            '21',
            'POINT(1.35500 44.01630)',
            '33',
            'POINT(1.35419 44.01665)',
        );

        $manager->persist($location1);
        $manager->persist($location1Bis);
        $manager->persist($location1Ter);
        $manager->persist($location2);
        $manager->persist($location2Bis);
        $manager->persist($location3);
        $manager->persist($locationNoMeasures);
        $manager->persist($locationCifs);
        $manager->flush();

        $this->addReference('location1', $location1);
        $this->addReference('location2', $location2);
        $this->addReference('location2Bis', $location2Bis);
        $this->addReference('location3', $location3);
        $this->addReference('locationNoMeasures', $locationNoMeasures);
        $this->addReference('locationCifs', $locationCifs);
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderFixture::class,
        ];
    }
}
