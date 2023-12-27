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
    public const UUID_DOES_NOT_EXIST = '0658c5da-47d6-7d16-8000-ea88577534a4';

    public const UUID_TYPICAL = '51449b82-5032-43c8-a427-46b9ddb44762';

    public const UUID_PUBLISHED = '2d79e1ff-c991-4767-b8c0-36b644038d0f';
    public const UUID_COMPLEX_VEHICLES = self::UUID_PUBLISHED;

    public const UUID_PERMANENT_ONLY_ONE = 'f15ed802-fa9b-4d75-ab04-d62ea46597e9';

    public const UUID_FULL_CITY = '0658c562-641f-75b5-8000-0acab688b2d7';

    public function load(ObjectManager $manager): void
    {
        $typicalRegulationOrderLocation1 = new Location(
            self::UUID_TYPICAL,
            $this->getReference('typicalRegulationOrder'),
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

        $typicalRegulationOrderLocation2 = new Location(
            '34247125-38f4-4e69-b5d7-5516a577d149',
            $this->getReference('typicalRegulationOrder'),
            cityCode: '44195',
            cityLabel: 'Savenay (44260)',
            roadName: 'Route du Lac',
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: null,
        );

        $typicalRegulationOrderLocation3 = new Location(
            '0b5d0ddf-f7aa-4f0a-af12-1f654a505200',
            $this->getReference('typicalRegulationOrder'),
            cityCode: '44195',
            cityLabel: 'Savenay (44260)',
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: null,
        );

        $publishedLocation1 = new Location(
            self::UUID_PUBLISHED,
            $this->getReference('publishedRegulationOrder'),
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

        $publishedLocation2 = new Location(
            '064ca782-771c-783f-8000-e67473eabea6',
            $this->getReference('publishedRegulationOrder'),
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

        $publishedLocation3 = new Location(
            '0655b3f6-124a-7f8d-8000-7c747883d40d',
            $this->getReference('publishedRegulationOrder'),
            // Full road, should not appear in DATEX export.
            cityCode: '82121',
            cityLabel: 'Montauban (82000)',
            roadName: 'Rue Gamot',
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: null,
        );

        $permanentRegulationOrderLocation = new Location(
            self::UUID_PERMANENT_ONLY_ONE,
            $this->getReference('regulationOrderPermanent'),
            cityCode: '75118',
            cityLabel: 'Paris 18e Arrondissement (75018)',
            roadName: 'Rue du Simplon',
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: null,
        );

        $fullCityLocation = new Location(
            self::UUID_FULL_CITY,
            $this->getReference('fullCityRegulationOrder'),
            cityCode: '75118',
            cityLabel: 'Paris 18e Arrondissement (75018)',
            roadName: null,
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

        $manager->persist($typicalRegulationOrderLocation1);
        $manager->persist($typicalRegulationOrderLocation2);
        $manager->persist($typicalRegulationOrderLocation3);
        $manager->persist($publishedLocation1);
        $manager->persist($publishedLocation2);
        $manager->persist($publishedLocation3);
        $manager->persist($fullCityLocation);
        $manager->persist($permanentRegulationOrderLocation);
        $manager->persist($locationNoMeasures);
        $manager->flush();

        $this->addReference('typicalLocation', $typicalRegulationOrderLocation1);
        $this->addReference('publishedLocation1', $publishedLocation1);
        $this->addReference('publishedLocation2', $publishedLocation2);
        $this->addReference('publishedLocation3', $publishedLocation3);
        $this->addReference('fullCityLocation', $fullCityLocation);
        $this->addReference('permanentRegulationOrderLocation', $permanentRegulationOrderLocation);
        $this->addReference('locationNoMeasures', $locationNoMeasures);
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderFixture::class,
        ];
    }
}
