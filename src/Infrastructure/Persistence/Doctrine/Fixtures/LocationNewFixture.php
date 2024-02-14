<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\LocationNew;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class LocationNewFixture extends Fixture implements DependentFixtureInterface
{
    public const UUID_DOES_NOT_EXIST = '0658c5da-47d6-7d16-8000-ea88577534a4';

    public const UUID_TYPICAL = '51449b82-5032-43c8-a427-46b9ddb44762';

    public const UUID_PUBLISHED = '2d79e1ff-c991-4767-b8c0-36b644038d0f';
    public const UUID_COMPLEX_VEHICLES = self::UUID_PUBLISHED;

    public const UUID_PERMANENT_ONLY_ONE = 'f15ed802-fa9b-4d75-ab04-d62ea46597e9';

    public const UUID_FULL_CITY = '0658c562-641f-75b5-8000-0acab688b2d7';

    public function load(ObjectManager $manager): void
    {
        $typicalMeasureLocation1 = new LocationNew(
            self::UUID_TYPICAL,
            $this->getReference('typicalMeasure'),
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
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

        $typicalMeasureLocation2 = new LocationNew(
            '34247125-38f4-4e69-b5d7-5516a577d149',
            $this->getReference('typicalMeasure'),
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
            cityCode: '44195',
            cityLabel: 'Savenay (44260)',
            roadName: 'Route du Lac',
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: json_encode(
                // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27rue%20victor%20hugo%27%20AND%20code_insee=44195&PropertyName=geometrie
                [
                    'type' => 'MultiLineString',
                    'coordinates' => [[[-1.9348411, 47.35982668], [-1.93457085, 47.35974617], [-1.93355284, 47.35943046], [-1.9331983, 47.35932827], [-1.93304583, 47.35928695]], [[-1.93523407, 47.35991369], [-1.9351106, 47.3598991], [-1.93495184, 47.35986165], [-1.9348411, 47.35982668]], [[-1.93523407, 47.35991369], [-1.93540225, 47.35992368], [-1.93549671, 47.35992598]], [[-1.93732403, 47.36010712], [-1.93756453, 47.36015281], [-1.93789122, 47.36021197], [-1.93794966, 47.36024106], [-1.9379971, 47.3602661]], [[-1.93658304, 47.35997448], [-1.93660982, 47.35997695], [-1.93675064, 47.35999261], [-1.93703461, 47.36004908], [-1.93732403, 47.36010712]], [[-1.93549671, 47.35992598], [-1.93623338, 47.35995418], [-1.93638685, 47.35996298]], [[-1.93638685, 47.35996298], [-1.93657767, 47.35997381], [-1.93658304, 47.35997448]]],
                ],
            ),
        );

        $typicalMeasureLocation3 = new LocationNew(
            '0b5d0ddf-f7aa-4f0a-af12-1f654a505200',
            $this->getReference('typicalMeasure'),
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
            cityCode: '44195',
            cityLabel: 'Savenay (44260)',
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: json_encode(
                // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27route%20du%20lac%27%20AND%20code_insee=44195&PropertyName=geometrie
                [
                    'type' => 'MultiLineString',
                    'coordinates' => [[[-1.93359977, 47.35748921], [-1.93346152, 47.35748697], [-1.93314592, 47.35749046], [-1.93286157, 47.35750165], [-1.9326444, 47.3575217], [-1.93250176, 47.357544], [-1.93236831, 47.35757944]], [[-1.93396173, 47.35749908], [-1.93380843, 47.35749207], [-1.93359977, 47.35748921]], [[-1.93576039, 47.35781676], [-1.93549925, 47.35776292], [-1.93517885, 47.35768545], [-1.93482349, 47.35760315], [-1.93456549, 47.35755459], [-1.93435682, 47.35752287], [-1.93411106, 47.35750626], [-1.93396173, 47.35749908]], [[-1.93913672, 47.35845874], [-1.93924292, 47.35848759], [-1.93933606, 47.35851881], [-1.93954565, 47.35858926], [-1.93987739, 47.35871765]], [[-1.93787253, 47.35819414], [-1.93845491, 47.35830556], [-1.93870367, 47.3583545]], [[-1.93870367, 47.3583545], [-1.93888218, 47.3583902], [-1.93913672, 47.35845874]], [[-1.93668514, 47.35798488], [-1.93605815, 47.35787896], [-1.93576039, 47.35781676]], [[-1.93668514, 47.35798488], [-1.93696968, 47.35803321], [-1.9376361, 47.35814918], [-1.93787253, 47.35819414]]],
                ],
            ),
        );

        $manager->persist($typicalMeasureLocation1);
        $manager->persist($typicalMeasureLocation2);
        $manager->persist($typicalMeasureLocation3);
        $manager->flush();

        $this->addReference('typicalMeasureLocation1', $typicalMeasureLocation1);
        $this->addReference('typicalMeasureLocation2', $typicalMeasureLocation2);
        $this->addReference('typicalMeasureLocation3', $typicalMeasureLocation3);
    }

    public function getDependencies(): array
    {
        return [
            MeasureFixture::class,
        ];
    }
}
