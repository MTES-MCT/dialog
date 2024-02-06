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

        $typicalRegulationOrderLocation2 = new Location(
            '34247125-38f4-4e69-b5d7-5516a577d149',
            $this->getReference('typicalRegulationOrder'),
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

        $typicalRegulationOrderLocation3 = new Location(
            '0b5d0ddf-f7aa-4f0a-af12-1f654a505200',
            $this->getReference('typicalRegulationOrder'),
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

        $publishedLocation1 = new Location(
            self::UUID_PUBLISHED,
            $this->getReference('publishedRegulationOrder'),
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
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
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
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
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
            cityCode: '82121',
            cityLabel: 'Montauban (82000)',
            roadName: 'Rue Gamot',
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: json_encode(
                // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27rue%20gamot%27%20AND%20code_insee=82121&PropertyName=geometrie
                [
                    'type' => 'MultiLineString',
                    'coordinates' => [[[1.34352783, 44.01741201], [1.34351021, 44.01728842], [1.34344305, 44.01672388]], [[1.34361127, 44.01827476], [1.34363309, 44.01855416], [1.34367982, 44.01909228], [1.34373623, 44.01964046], [1.34376444, 44.02004327]], [[1.34355908, 44.01762403], [1.34352783, 44.01741201]], [[1.34361127, 44.01827476], [1.34359579, 44.01799187], [1.34355908, 44.01762403]]],
                ],
            ),
        );

        $permanentRegulationOrderLocation = new Location(
            self::UUID_PERMANENT_ONLY_ONE,
            $this->getReference('regulationOrderPermanent'),
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
            cityCode: '75118',
            cityLabel: 'Paris 18e Arrondissement (75018)',
            roadName: 'Rue du Simplon',
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: json_encode(
                // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27rue%20du%20simplon%27%20AND%20code_insee=75118&PropertyName=geometrie
                [
                    'type' => 'MultiLineString',
                    'coordinates' => [[[2.35263563, 48.89325427], [2.35117787, 48.89356294], [2.35100967, 48.893598]], [[2.35100967, 48.893598], [2.3492403, 48.89406594]], [[2.3457314, 48.8950073], [2.3463186, 48.89484233]], [[2.3463186, 48.89484233], [2.3471424, 48.89462649]], [[2.34777601, 48.89445727], [2.3492403, 48.89406594]], [[2.3471424, 48.89462649], [2.34777601, 48.89445727]]],
                ],
            ),
        );

        $fullCityLocation = new Location(
            self::UUID_FULL_CITY,
            $this->getReference('fullCityRegulationOrder'),
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
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

        $cifsLocation = new Location(
            '06548f85-d545-7b45-8000-8a23c45850b3',
            $this->getReference('regulationOrderCifs'),
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
            cityCode: '82121',
            cityLabel: 'Montauban (82000)',
            roadName: 'Rue de la République',
            fromHouseNumber: '21',
            toHouseNumber: '33',
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(1.35500, 44.01630),
                Coordinates::fromLonLat(1.35419, 44.01665),
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
        $manager->persist($cifsLocation);
        $manager->flush();

        $this->addReference('typicalLocation', $typicalRegulationOrderLocation1);
        $this->addReference('publishedLocation1', $publishedLocation1);
        $this->addReference('publishedLocation2', $publishedLocation2);
        $this->addReference('publishedLocation3', $publishedLocation3);
        $this->addReference('fullCityLocation', $fullCityLocation);
        $this->addReference('permanentRegulationOrderLocation', $permanentRegulationOrderLocation);
        $this->addReference('locationNoMeasures', $locationNoMeasures);
        $this->addReference('cifsLocation', $cifsLocation);
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderFixture::class,
        ];
    }
}
