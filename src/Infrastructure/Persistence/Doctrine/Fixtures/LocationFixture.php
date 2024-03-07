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
        $typicalMeasureLocation1 = new Location(
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
            geometry: '{"type":"LineString","crs":{"type":"name","properties":{"name":"EPSG:2154"}},"coordinates":[[-1.935821678,47.347000002],[-1.9357484,47.34702236],[-1.9353862,47.34710992],[-1.93490428,47.34722332],[-1.93424563,47.34737761],[-1.93374519,47.34750623],[-1.93340389,47.34759019],[-1.93299607,47.34768691],[-1.93291188,47.34770943],[-1.93268153,47.34777334],[-1.93248952,47.34782119],[-1.93234592,47.34784714],[-1.93207756,47.34785854],[-1.93140947,47.3478671],[-1.93097041,47.347872202]]}',
            // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27route%20du%20grand%20brossais%27%20AND%20code_insee=44195&PropertyName=geometrie,id_pseudo_fpb
            roadLineGeometry: '{"type":"MultiLineString","coordinates":[[[-1.93900561,47.34611977],[-1.93881261,47.34614242],[-1.93862895,47.34618]],[[-1.94156143,47.34627613],[-1.94141949,47.34627676],[-1.94107,47.3462718],[-1.94073762,47.34626521],[-1.94044736,47.34624059],[-1.94002252,47.34619554],[-1.93967674,47.34615885],[-1.93940971,47.34612692],[-1.93920009,47.3461133],[-1.93900561,47.34611977]],[[-1.94782475,47.34625455],[-1.94772148,47.34625715],[-1.94736744,47.34624609],[-1.94687427,47.34622292],[-1.94662516,47.34621279],[-1.94604524,47.3461843],[-1.94554728,47.34616674],[-1.94501983,47.34616036]],[[-1.93862895,47.34618],[-1.93856253,47.34619365],[-1.93810681,47.34628881],[-1.93776551,47.34637279],[-1.93753691,47.34644115],[-1.93732707,47.34654028]],[[-1.94406201,47.34619306],[-1.94356307,47.346208],[-1.9430443,47.34622379],[-1.94264103,47.34624007],[-1.94217406,47.34625635],[-1.941718,47.34627578],[-1.94156143,47.34627613]],[[-1.94501983,47.34616036],[-1.94455905,47.34617188],[-1.94406201,47.34619306]],[[-1.93625155,47.34686565],[-1.93618471,47.34688924],[-1.9357484,47.34702236]],[[-1.92909849,47.34789133],[-1.92841676,47.34789593],[-1.92816427,47.34789223]],[[-1.9357484,47.34702236],[-1.9353862,47.34710992],[-1.93490428,47.34722332],[-1.93424563,47.34737761],[-1.93374519,47.34750623],[-1.93340389,47.34759019],[-1.93299607,47.34768691],[-1.93291188,47.34770943]],[[-1.9299491,47.34789037],[-1.92909849,47.34789133]],[[-1.93291188,47.34770943],[-1.93268153,47.34777334],[-1.93248952,47.34782119],[-1.93234592,47.34784714],[-1.93207756,47.34785854],[-1.93140947,47.3478671],[-1.93070816,47.34787525],[-1.93059414,47.34787649],[-1.9299491,47.34789037]],[[-1.93732707,47.34654028],[-1.93719248,47.34653428],[-1.9369894,47.3465339]],[[-1.9369894,47.3465339],[-1.93693099,47.34659141],[-1.93684045,47.34664577],[-1.93673693,47.34670339],[-1.93656674,47.34675754],[-1.93625155,47.34686565]]]}',
            roadLineId: '441950137',
        );

        $typicalMeasureLocation2 = new Location(
            '34247125-38f4-4e69-b5d7-5516a577d149',
            $this->getReference('typicalMeasure'),
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
            cityCode: '44195',
            cityLabel: 'Savenay (44260)',
            roadName: 'Rue Victor Hugo',
            fromHouseNumber: null,
            toHouseNumber: null,
            // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27rue%20victor%20hugo%27%20AND%20code_insee=44195&PropertyName=geometrie
            geometry: '{"type":"MultiLineString","coordinates":[[[-1.9348411,47.35982668],[-1.93457085,47.35974617],[-1.93355284,47.35943046],[-1.9331983,47.35932827],[-1.93304583,47.35928695]],[[-1.93523407,47.35991369],[-1.9351106,47.3598991],[-1.93495184,47.35986165],[-1.9348411,47.35982668]],[[-1.93523407,47.35991369],[-1.93540225,47.35992368],[-1.93549671,47.35992598]],[[-1.93732403,47.36010712],[-1.93756453,47.36015281],[-1.93789122,47.36021197],[-1.93794966,47.36024106],[-1.9379971,47.3602661]],[[-1.93658304,47.35997448],[-1.93660982,47.35997695],[-1.93675064,47.35999261],[-1.93703461,47.36004908],[-1.93732403,47.36010712]],[[-1.93549671,47.35992598],[-1.93623338,47.35995418],[-1.93638685,47.35996298]],[[-1.93638685,47.35996298],[-1.93657767,47.35997381],[-1.93658304,47.35997448]]]}',
            roadLineGeometry: '{"type":"MultiLineString","coordinates":[[[-1.9348411,47.35982668],[-1.93457085,47.35974617],[-1.93355284,47.35943046],[-1.9331983,47.35932827],[-1.93304583,47.35928695]],[[-1.93523407,47.35991369],[-1.9351106,47.3598991],[-1.93495184,47.35986165],[-1.9348411,47.35982668]],[[-1.93523407,47.35991369],[-1.93540225,47.35992368],[-1.93549671,47.35992598]],[[-1.93732403,47.36010712],[-1.93756453,47.36015281],[-1.93789122,47.36021197],[-1.93794966,47.36024106],[-1.9379971,47.3602661]],[[-1.93658304,47.35997448],[-1.93660982,47.35997695],[-1.93675064,47.35999261],[-1.93703461,47.36004908],[-1.93732403,47.36010712]],[[-1.93549671,47.35992598],[-1.93623338,47.35995418],[-1.93638685,47.35996298]],[[-1.93638685,47.35996298],[-1.93657767,47.35997381],[-1.93658304,47.35997448]]]}',
            roadLineId: '441951520',
        );

        $typicalMeasureLocation3 = new Location(
            '0b5d0ddf-f7aa-4f0a-af12-1f654a505200',
            $this->getReference('typicalMeasureToRemove'),
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
            cityCode: '44195',
            cityLabel: 'Savenay (44260)',
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: null,
            toHouseNumber: null,
            // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27route%20du%20grand%20brossais%27%20AND%20code_insee=44195&PropertyName=geometrie,id_pseudo_fpb
            geometry: '{"type":"MultiLineString","coordinates":[[[-1.93900561,47.34611977],[-1.93881261,47.34614242],[-1.93862895,47.34618]],[[-1.94156143,47.34627613],[-1.94141949,47.34627676],[-1.94107,47.3462718],[-1.94073762,47.34626521],[-1.94044736,47.34624059],[-1.94002252,47.34619554],[-1.93967674,47.34615885],[-1.93940971,47.34612692],[-1.93920009,47.3461133],[-1.93900561,47.34611977]],[[-1.94782475,47.34625455],[-1.94772148,47.34625715],[-1.94736744,47.34624609],[-1.94687427,47.34622292],[-1.94662516,47.34621279],[-1.94604524,47.3461843],[-1.94554728,47.34616674],[-1.94501983,47.34616036]],[[-1.93862895,47.34618],[-1.93856253,47.34619365],[-1.93810681,47.34628881],[-1.93776551,47.34637279],[-1.93753691,47.34644115],[-1.93732707,47.34654028]],[[-1.94406201,47.34619306],[-1.94356307,47.346208],[-1.9430443,47.34622379],[-1.94264103,47.34624007],[-1.94217406,47.34625635],[-1.941718,47.34627578],[-1.94156143,47.34627613]],[[-1.94501983,47.34616036],[-1.94455905,47.34617188],[-1.94406201,47.34619306]],[[-1.93625155,47.34686565],[-1.93618471,47.34688924],[-1.9357484,47.34702236]],[[-1.92909849,47.34789133],[-1.92841676,47.34789593],[-1.92816427,47.34789223]],[[-1.9357484,47.34702236],[-1.9353862,47.34710992],[-1.93490428,47.34722332],[-1.93424563,47.34737761],[-1.93374519,47.34750623],[-1.93340389,47.34759019],[-1.93299607,47.34768691],[-1.93291188,47.34770943]],[[-1.9299491,47.34789037],[-1.92909849,47.34789133]],[[-1.93291188,47.34770943],[-1.93268153,47.34777334],[-1.93248952,47.34782119],[-1.93234592,47.34784714],[-1.93207756,47.34785854],[-1.93140947,47.3478671],[-1.93070816,47.34787525],[-1.93059414,47.34787649],[-1.9299491,47.34789037]],[[-1.93732707,47.34654028],[-1.93719248,47.34653428],[-1.9369894,47.3465339]],[[-1.9369894,47.3465339],[-1.93693099,47.34659141],[-1.93684045,47.34664577],[-1.93673693,47.34670339],[-1.93656674,47.34675754],[-1.93625155,47.34686565]]]}',
            roadLineGeometry: '{"type":"MultiLineString","coordinates":[[[-1.93900561,47.34611977],[-1.93881261,47.34614242],[-1.93862895,47.34618]],[[-1.94156143,47.34627613],[-1.94141949,47.34627676],[-1.94107,47.3462718],[-1.94073762,47.34626521],[-1.94044736,47.34624059],[-1.94002252,47.34619554],[-1.93967674,47.34615885],[-1.93940971,47.34612692],[-1.93920009,47.3461133],[-1.93900561,47.34611977]],[[-1.94782475,47.34625455],[-1.94772148,47.34625715],[-1.94736744,47.34624609],[-1.94687427,47.34622292],[-1.94662516,47.34621279],[-1.94604524,47.3461843],[-1.94554728,47.34616674],[-1.94501983,47.34616036]],[[-1.93862895,47.34618],[-1.93856253,47.34619365],[-1.93810681,47.34628881],[-1.93776551,47.34637279],[-1.93753691,47.34644115],[-1.93732707,47.34654028]],[[-1.94406201,47.34619306],[-1.94356307,47.346208],[-1.9430443,47.34622379],[-1.94264103,47.34624007],[-1.94217406,47.34625635],[-1.941718,47.34627578],[-1.94156143,47.34627613]],[[-1.94501983,47.34616036],[-1.94455905,47.34617188],[-1.94406201,47.34619306]],[[-1.93625155,47.34686565],[-1.93618471,47.34688924],[-1.9357484,47.34702236]],[[-1.92909849,47.34789133],[-1.92841676,47.34789593],[-1.92816427,47.34789223]],[[-1.9357484,47.34702236],[-1.9353862,47.34710992],[-1.93490428,47.34722332],[-1.93424563,47.34737761],[-1.93374519,47.34750623],[-1.93340389,47.34759019],[-1.93299607,47.34768691],[-1.93291188,47.34770943]],[[-1.9299491,47.34789037],[-1.92909849,47.34789133]],[[-1.93291188,47.34770943],[-1.93268153,47.34777334],[-1.93248952,47.34782119],[-1.93234592,47.34784714],[-1.93207756,47.34785854],[-1.93140947,47.3478671],[-1.93070816,47.34787525],[-1.93059414,47.34787649],[-1.9299491,47.34789037]],[[-1.93732707,47.34654028],[-1.93719248,47.34653428],[-1.9369894,47.3465339]],[[-1.9369894,47.3465339],[-1.93693099,47.34659141],[-1.93684045,47.34664577],[-1.93673693,47.34670339],[-1.93656674,47.34675754],[-1.93625155,47.34686565]]]}',
            roadLineId: '441950137',
        );

        $publishedLocation1 = new Location(
            self::UUID_PUBLISHED,
            $this->getReference('publishedMeasure'),
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
            roadLineGeometry: null,
            roadLineId: null,
        );

        $publishedLocation2 = new Location(
            '064ca782-771c-783f-8000-e67473eabea6',
            $this->getReference('publishedMeasure'),
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
            cityCode: '82121',
            cityLabel: 'Montauban (82000)',
            roadName: "Rue de l'Hôtel de Ville",
            fromHouseNumber: '30',
            toHouseNumber: '12',
            geometry: '{"type":"LineString","crs":{"type":"name","properties":{"name":"EPSG:2154"}},"coordinates":[[1.352105256,44.016810494],[1.35213207,44.01678578],[1.35216909,44.01675663],[1.35221741,44.01672495],[1.35226814,44.01669601],[1.35232384,44.01666804],[1.35272189,44.0164913],[1.35280254,44.01646281],[1.35301215,44.016390795]]}',
            // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27rue%20de%20l%27%27h%C3%B4tel%20de%20ville%27%20AND%20code_insee=82121&PropertyName=geometrie,id_pseudo_fpb
            roadLineGeometry: '{"type":"MultiLineString","coordinates":[[[1.35312635,44.01635156],[1.35350683,44.01622135],[1.35373561,44.01616088]],[[1.35209747,44.01681767],[1.35213207,44.01678578],[1.35216909,44.01675663],[1.35221741,44.01672495],[1.35226814,44.01669601],[1.35232384,44.01666804],[1.35272189,44.0164913],[1.35280254,44.01646281],[1.35312635,44.01635156]],[[1.35176034,44.01721678],[1.35189402,44.01716658]],[[1.3520213,44.01690654],[1.35206159,44.01685043],[1.35207058,44.01684157],[1.35209747,44.01681767]],[[1.35189402,44.01716658],[1.35193323,44.01710506],[1.35195601,44.01705049],[1.35197498,44.01699856],[1.35199236,44.01695832],[1.35201088,44.01692169],[1.3520213,44.01690654]]]}',
            roadLineId: '821213822',
        );

        $publishedLocation3 = new Location(
            '0655b3f6-124a-7f8d-8000-7c747883d40d',
            $this->getReference('publishedMeasure'),
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
            cityCode: '82121',
            cityLabel: 'Montauban (82000)',
            roadName: 'Rue Gamot',
            fromHouseNumber: null,
            toHouseNumber: null,
            // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27rue%20gamot%27%20AND%20code_insee=82121&PropertyName=geometrie,id_pseudo_fpb
            geometry: '{"type":"MultiLineString","crs":{"type":"name","properties":{"name":"EPSG:2154"}},"coordinates":[[[1.34352783,44.01741201],[1.34351021,44.01728842],[1.34344305,44.01672388]],[[1.34361127,44.01827476],[1.34363309,44.01855416],[1.34367982,44.01909228],[1.34373623,44.01964046],[1.34376444,44.02004327]],[[1.34355908,44.01762403],[1.34352783,44.01741201]],[[1.34361127,44.01827476],[1.34359579,44.01799187],[1.34355908,44.01762403]]]}',
            roadLineGeometry: '{"type":"MultiLineString","crs":{"type":"name","properties":{"name":"EPSG:2154"}},"coordinates":[[[1.34352783,44.01741201],[1.34351021,44.01728842],[1.34344305,44.01672388]],[[1.34361127,44.01827476],[1.34363309,44.01855416],[1.34367982,44.01909228],[1.34373623,44.01964046],[1.34376444,44.02004327]],[[1.34355908,44.01762403],[1.34352783,44.01741201]],[[1.34361127,44.01827476],[1.34359579,44.01799187],[1.34355908,44.01762403]]]}',
            roadLineId: '821213270',
        );

        $permanentRegulationOrderLocation = new Location(
            self::UUID_PERMANENT_ONLY_ONE,
            $this->getReference('permanentMeasure'),
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
            cityCode: '75118',
            cityLabel: 'Paris 18e Arrondissement (75018)',
            roadName: 'Rue du Simplon',
            fromHouseNumber: null,
            toHouseNumber: null,
            // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27rue%20du%20simplon%27%20AND%20code_insee=75118&PropertyName=geometrie,id_pseudo_fpb
            geometry: '{"type":"MultiLineString","crs":{"type":"name","properties":{"name":"EPSG:2154"}},"coordinates":[[[2.35263563,48.89325427],[2.35117787,48.89356294],[2.35100967,48.893598]],[[2.35100967,48.893598],[2.3492403,48.89406594]],[[2.3457314,48.8950073],[2.3463186,48.89484233]],[[2.3463186,48.89484233],[2.3471424,48.89462649]],[[2.34777601,48.89445727],[2.3492403,48.89406594]],[[2.3471424,48.89462649],[2.34777601,48.89445727]]]}',
            roadLineGeometry: '{"type":"MultiLineString","crs":{"type":"name","properties":{"name":"EPSG:2154"}},"coordinates":[[[2.35263563,48.89325427],[2.35117787,48.89356294],[2.35100967,48.893598]],[[2.35100967,48.893598],[2.3492403,48.89406594]],[[2.3457314,48.8950073],[2.3463186,48.89484233]],[[2.3463186,48.89484233],[2.3471424,48.89462649]],[[2.34777601,48.89445727],[2.3492403,48.89406594]],[[2.3471424,48.89462649],[2.34777601,48.89445727]]]}',
            roadLineId: '751188986',
        );

        $fullCityLocation = new Location(
            self::UUID_FULL_CITY,
            $this->getReference('fullCityMeasure'),
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
            cityCode: '75118',
            cityLabel: 'Paris 18e Arrondissement (75018)',
            roadName: null,
            fromHouseNumber: null,
            toHouseNumber: null,
            geometry: null,
            roadLineGeometry: null,
            roadLineId: null,
        );

        $cifsLocation = new Location(
            '06548f85-d545-7b45-8000-8a23c45850b3',
            $this->getReference('cifsMeasure'),
            roadType: 'lane',
            administrator: null,
            roadNumber: null,
            cityCode: '82121',
            cityLabel: 'Montauban (82000)',
            roadName: 'Rue de la République',
            fromHouseNumber: null,
            toHouseNumber: '33',
            geometry: '{"type":"LineString","crs":{"type":"name","properties":{"name":"EPSG:2154"}},"coordinates":[[1.35643852,44.01573612],[1.35634358,44.01578421],[1.35628051,44.01580846],[1.35620232,44.01583789],[1.35573093,44.01600635],[1.35515052,44.01623528],[1.3550483,44.01627605],[1.35476043,44.01639595],[1.35431163,44.01660254],[1.354256366,44.016628823]]}',
            // Source: https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDTOPO_V3:voie_nommee&cql_filter=nom_minuscule=%27rue%20de%20la%20r%C3%A9publique%27%20AND%20code_insee=82121&PropertyName=geometrie,id_pseudo_fpb
            // Reworked to use a single part (road line returned by IGN appears fragmented in 2 parts)
            roadLineGeometry: '{"type":"MultiLineString","crs":{"type":"name","properties":{"name":"EPSG:2154"}},"coordinates":[[[1.35643852,44.01573612],[1.35634358,44.01578421],[1.35628051,44.01580846],[1.35620232,44.01583789],[1.35573093,44.01600635],[1.35515052,44.01623528],[1.3550483,44.01627605],[1.35476043,44.01639595],[1.35431163,44.01660254],[1.35418135,44.0166645],[1.35375252,44.01687049],[1.35354078,44.01698883],[1.35332393,44.01711159],[1.35325547,44.01714927],[1.35275247,44.01741806],[1.3527081,44.0174426],[1.35249763,44.01756005],[1.35244427,44.01759346],[1.35218587,44.01777231],[1.35197056,44.0179275],[1.35183958,44.01801374],[1.35155475,44.01820029]]]}',
            roadLineId: '821216800',
        );

        $manager->persist($typicalMeasureLocation1);
        $manager->persist($typicalMeasureLocation2);
        $manager->persist($typicalMeasureLocation3);
        $manager->persist($publishedLocation1);
        $manager->persist($publishedLocation2);
        $manager->persist($publishedLocation3);
        $manager->persist($permanentRegulationOrderLocation);
        $manager->persist($fullCityLocation);
        $manager->persist($cifsLocation);
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
