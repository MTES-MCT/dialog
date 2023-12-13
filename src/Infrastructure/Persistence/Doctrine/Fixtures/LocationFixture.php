<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
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
            cityCode: '44195',
            cityLabel: 'Savenay (44260)',
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: '15',
            toHouseNumber: '37bis',
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(-1.935836, 47.347024),
                Coordinates::fromLonLat(-1.930973, 47.347917),
            ]),
        );

        $location1Bis = new Location(
            '34247125-38f4-4e69-b5d7-5516a577d149',
            $this->getReference('regulationOrder'),
            cityCode: '44195',
            cityLabel: 'Savenay (44260)',
            roadName: 'Rue Victor Hugo',
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: null,
        );

        $location1Ter = new Location(
            '0b5d0ddf-f7aa-4f0a-af12-1f654a505200',
            $this->getReference('regulationOrder'),
            cityCode: '44195',
            cityLabel: 'Savenay (44260)',
            roadName: 'Route du Lac',
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: null,
        );

        $location2 = new Location(
            '2d79e1ff-c991-4767-b8c0-36b644038d0f',
            $this->getReference('regulationOrder2'),
            cityCode: '82121',
            cityLabel: 'Montauban (82000)',
            roadName: 'Avenue de Fonneuve',
            fromHouseNumber: '695',
            toHouseNumber: '253',
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(1.362275, 44.028996),
                Coordinates::fromLonLat(1.35931, 44.025665),
            ]),
        );

        $location2Bis = new Location(
            '064ca782-771c-783f-8000-e67473eabea6',
            $this->getReference('regulationOrder2'),
            cityCode: '82121',
            cityLabel: 'Montauban (82000)',
            roadName: "Rue de l'Hôtel de Ville",
            fromHouseNumber: '30',
            toHouseNumber: '12',
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(1.352126, 44.016833),
                Coordinates::fromLonLat(1.353016, 44.016402),
            ]),
        );

        $location2Ter = new Location(
            '0655b3f6-124a-7f8d-8000-7c747883d40d',
            $this->getReference('regulationOrder2'),
            // Full road, should not appear in DATEX export.
            cityCode: '82121',
            cityLabel: 'Montauban (82000)',
            roadName: 'Rue Gamot',
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: null,
        );

        $location3 = new Location(
            'f15ed802-fa9b-4d75-ab04-d62ea46597e9',
            $this->getReference('regulationOrder3'),
            cityCode: '75118',
            cityLabel: 'Paris 18e Arrondissement (75018)',
            roadName: 'Rue du Simplon',
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: null,
        );

        $locationNoMeasures = new Location(
            '06500383-ad31-7e57-8000-e080e2755bd4',
            $this->getReference('regulationOrderNoMeasures'),
            cityCode: '44195',
            cityLabel: 'Savenay (44260)',
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: '15',
            toHouseNumber: '37bis',
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(-1.935836, 47.347024),
                Coordinates::fromLonLat(-1.930973, 47.347917),
            ]),
        );

        $manager->persist($location1);
        $manager->persist($location1Bis);
        $manager->persist($location1Ter);
        $manager->persist($location2);
        $manager->persist($location2Bis);
        $manager->persist($location2Ter);
        $manager->persist($location3);
        $manager->persist($locationNoMeasures);
        $manager->flush();

        $this->addReference('location1', $location1);
        $this->addReference('location2', $location2);
        $this->addReference('location2Bis', $location2Bis);
        $this->addReference('location2Ter', $location2Ter);
        $this->addReference('location3', $location3);
        $this->addReference('locationNoMeasures', $locationNoMeasures);
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderFixture::class,
        ];
    }
}
