<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Location\RawGeoJSON;
use App\Domain\Regulation\Measure;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class LocationFixture extends Fixture implements DependentFixtureInterface
{
    public const UUID_DOES_NOT_EXIST = '0658c5da-47d6-7d16-8000-ea88577534a4';

    public const UUID_TYPICAL = '51449b82-5032-43c8-a427-46b9ddb44762';

    public const UUID_PUBLISHED = '2d79e1ff-c991-4767-b8c0-36b644038d0f';
    public const UUID_PUBLISHED2 = '064ca782-771c-783f-8000-e67473eabea6';
    public const UUID_PUBLISHED3 = '0655b3f6-124a-7f8d-8000-7c747883d40d';
    public const UUID_PUBLISHED4 = '065f9436-0ff3-74e3-8000-d9ac9b5a16ad';
    public const UUID_COMPLEX_VEHICLES = self::UUID_PUBLISHED;

    public const UUID_PERMANENT_ONLY_ONE = 'f15ed802-fa9b-4d75-ab04-d62ea46597e9';

    public const UUID_FULL_CITY = '0658c562-641f-75b5-8000-0acab688b2d7';
    public const UUID_CIFS_NAMED_STREET = '06548f85-d545-7b45-8000-8a23c45850b3';
    public const UUID_CIFS_DEPARTMENTAL_ROAD = '065f94ef-ea0a-7ab5-8000-bd5686102151';
    public const UUID_OUTDATED_CIFS = 'ad7b675a-92d8-4556-a0e6-09cc66eb259a';
    public const UUID_LITTERALIS = '066e984f-4746-78f8-8000-dce555b28604';

    public function load(ObjectManager $manager): void
    {
        $typicalMeasureLocation1 = new Location(
            self::UUID_TYPICAL,
            $this->getReference('typicalMeasure', Measure::class),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"GeometryCollection","geometries":[{"type":"LineString","coordinates":[[2.348095478,48.905107817],[2.347831443,48.905214935]]},{"type":"LineString","coordinates":[[2.349141495,48.904685153],[2.348095478,48.905107817]]}]}',
        );

        $namedStreetTypicalMeasureLocation1 = new NamedStreet(
            uuid: '047d007c-f468-4f80-9f8c-11cd19cfe0c1',
            location: $typicalMeasureLocation1,
            direction: DirectionEnum::A_TO_B->value,
            cityCode: '93070',
            cityLabel: 'Saint-Ouen-sur-Seine',
            roadName: 'Rue Eugène Berthoud',
            fromHouseNumber: '47',
            toHouseNumber: '65',
        );

        $typicalMeasureLocation2 = new Location(
            '34247125-38f4-4e69-b5d7-5516a577d149',
            $this->getReference('typicalMeasure', Measure::class),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"MultiLineString","coordinates":[[[2.34941313,48.904575394],[2.349815692,48.905220588],[2.349865187,48.905299097]],[[2.348042797,48.901362789],[2.348130719,48.901749074],[2.348215642,48.902048108]],[[2.348713604,48.903460941],[2.348878133,48.903721737],[2.34892758,48.903803844]],[[2.348660065,48.903378813],[2.348713604,48.903460941]],[[2.34837466,48.90274864],[2.34837003,48.902900599]],[[2.348215642,48.902048108],[2.34837466,48.90274864]],[[2.348582536,48.903251588],[2.348448775,48.903039522]],[[2.348660065,48.903378813],[2.348582536,48.903251588]],[[2.34892758,48.903803844],[2.34941313,48.904575394]],[[2.348448775,48.903039522],[2.34837003,48.902900599]],[[2.349865187,48.905299097],[2.350063146,48.90561493]]]}',
        );

        $namedStreetTypicalMeasureLocation2 = new NamedStreet(
            uuid: 'b291d4ef-9a78-43c4-9f4f-babc256ac320',
            location: $typicalMeasureLocation2,
            direction: DirectionEnum::BOTH->value,
            cityCode: '93070',
            cityLabel: 'Saint-Ouen-sur-Seine',
            roadName: 'Rue Adrien Lesesne',
            fromHouseNumber: null,
            toHouseNumber: null,
        );

        $typicalMeasureLocation3 = new Location(
            '7d3a6e4f-0cc0-4c08-94da-45e75c399ac6',
            $this->getReference('typicalMeasure', Measure::class),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"GeometryCollection","geometries":[{"type":"LineString","coordinates":[[2.344170611,48.908685433],[2.344167806,48.90739221],[2.344169986,48.907327472]]},{"type":"LineString","coordinates":[[2.344180471,48.909310507],[2.344170611,48.908685433]]},{"type":"LineString","coordinates":[[2.34418455,48.909528162],[2.344180471,48.909310507]]},{"type":"LineString","coordinates":[[2.344186257,48.90964732],[2.34418455,48.909528162]]}]}',
        );

        $namedStreetTypicalMeasureLocation3 = new NamedStreet(
            uuid: 'f8f38bf6-6dec-4a0a-aa23-0d934cc49e55',
            location: $typicalMeasureLocation3,
            direction: DirectionEnum::BOTH->value,
            cityCode: '93070',
            cityLabel: 'Saint-Ouen-sur-Seine',
            roadName: 'Avenue Michelet',
            fromRoadName: 'Allée Isabeau',
            toRoadName: 'Avenue Du Cimetière',
        );

        $typicalMeasureToRemoveLocation1 = new Location(
            '0b5d0ddf-f7aa-4f0a-af12-1f654a505200',
            $this->getReference('typicalMeasureToRemove', Measure::class),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"MultiLineString","coordinates":[[[2.334923946,48.912807243],[2.335730351,48.912376453],[2.335943492,48.912246336]],[[2.339762641,48.908572187],[2.339875555,48.908490073],[2.339912742,48.908462399],[2.340054611,48.908356163]],[[2.339660755,48.908645369],[2.339762641,48.908572187]],[[2.339390724,48.90885252],[2.339476263,48.90877745],[2.339660755,48.908645369]],[[2.340054611,48.908356163],[2.340401624,48.90810267]],[[2.338404355,48.909709514],[2.33849407,48.909628172],[2.338663732,48.909482522]],[[2.337641929,48.910441835],[2.337785996,48.910270863],[2.338027518,48.910053666]],[[2.337514404,48.910599408],[2.337560156,48.910542105],[2.337641929,48.910441835]],[[2.339034796,48.909162617],[2.339390724,48.90885252]],[[2.336594407,48.91138211],[2.336719599,48.911300967],[2.336811715,48.911245719],[2.337191447,48.910889896],[2.337257802,48.910821916],[2.337404313,48.910673441],[2.337514404,48.910599408]],[[2.345119799,48.904702183],[2.344646802,48.904997268],[2.344536677,48.905075805],[2.344154011,48.905347101]],[[2.342152019,48.906805598],[2.343886993,48.905534494],[2.344154011,48.905347101]],[[2.340962142,48.907687576],[2.34122642,48.907502873],[2.341373842,48.907388572]],[[2.341373842,48.907388572],[2.341716852,48.907126959]],[[2.341716852,48.907126959],[2.342152019,48.906805598]],[[2.34837003,48.902900599],[2.34851757,48.902774598],[2.348546395,48.902760366]],[[2.347519516,48.903279076],[2.347193065,48.903421188],[2.347072193,48.903487078]],[[2.348809501,48.902447037],[2.348546395,48.902760366]],[[2.347668898,48.903224133],[2.347519516,48.903279076]],[[2.347072193,48.903487078],[2.346755964,48.903684102],[2.346223867,48.904015743],[2.345676645,48.904356293],[2.345119799,48.904702183]],[[2.34837003,48.902900599],[2.34825704,48.902990815],[2.347756648,48.903188638],[2.347668898,48.903224133]],[[2.349173697,48.902012849],[2.348809501,48.902447037]],[[2.336363684,48.911928511],[2.336399648,48.91189004]],[[2.33470118,48.912622548],[2.334710811,48.912616306],[2.335187202,48.912279013]],[[2.336716252,48.911456528],[2.336658127,48.911520957],[2.336605402,48.911589911],[2.336460617,48.911816634],[2.336453658,48.911827387],[2.336399648,48.91189004]],[[2.335869687,48.911828648],[2.33577077,48.911882058],[2.335187202,48.912279013]],[[2.335869687,48.911828648],[2.336019564,48.911737749]],[[2.335943492,48.912246336],[2.336219871,48.912078798],[2.336287513,48.91201712],[2.336295779,48.912010871],[2.336363684,48.911928511]],[[2.336019564,48.911737749],[2.33638674,48.91151136],[2.336522903,48.91142578],[2.336594407,48.91138211]],[[2.337514404,48.910599408],[2.337481709,48.910702647],[2.337333776,48.910855611]],[[2.337333776,48.910855611],[2.337341941,48.910964472],[2.337171045,48.91120634],[2.336876343,48.911634578],[2.336815318,48.91171248],[2.336757549,48.911749032],[2.336552643,48.911875599],[2.336399648,48.91189004]],[[2.338663732,48.909482522],[2.339034796,48.909162617]],[[2.338027518,48.910053666],[2.338404355,48.909709514]],[[2.340836863,48.907776818],[2.340962142,48.907687576]],[[2.33681723,48.911348271],[2.336716252,48.911456528]],[[2.340401624,48.90810267],[2.340425031,48.908085712]],[[2.33681723,48.911348271],[2.336883586,48.911280291],[2.337081048,48.911095227],[2.337266069,48.910922684],[2.337333776,48.910855611]],[[2.340425031,48.908085712],[2.340836863,48.907776818]]]}',
        );

        $namedStreetTypicalMeasureToRemoveLocation1 = new NamedStreet(
            uuid: 'b640220b-a80b-45b7-915c-b1fc878ada9a',
            location: $typicalMeasureToRemoveLocation1,
            direction: DirectionEnum::BOTH->value,
            cityCode: '93070',
            cityLabel: 'Saint-Ouen-sur-Seine',
            roadName: 'Rue du Docteur Bauer',
            fromHouseNumber: null,
            toHouseNumber: null,
        );

        $publishedLocation1 = new Location(
            self::UUID_PUBLISHED,
            $this->getReference('publishedMeasure', Measure::class),
            roadType: RoadTypeEnum::LANE->value,
            geometry: ' {"type":"GeometryCollection","geometries":[{"type":"LineString","coordinates":[[2.330977507,48.915717906],[2.330750912,48.91595566]]},{"type":"LineString","coordinates":[[2.331071063,48.915609611],[2.330977507,48.915717906]]}]}',
        );

        $namedStreetPublishedLocation1 = new NamedStreet(
            uuid: 'e998d4bc-2e4a-4686-9105-e06869ddb24b',
            location: $publishedLocation1,
            direction: DirectionEnum::BOTH->value,
            cityCode: '93070',
            cityLabel: 'Saint-Ouen-sur-Seine',
            roadName: 'Rue Albert Dhalenne',
            fromHouseNumber: '12',
            toHouseNumber: '34',
        );

        $publishedLocation2 = new Location(
            self::UUID_PUBLISHED2,
            $this->getReference('publishedMeasure', Measure::class),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"LineString","coordinates":[[2.325355408,48.911434195],[2.325111381,48.911736147],[2.324961168,48.911921009]]}',
        );

        $namedStreetPublishedLocation2 = new NamedStreet(
            uuid: '1578630f-06d7-457a-bfde-8447be156107',
            location: $publishedLocation2,
            direction: DirectionEnum::BOTH->value,
            cityCode: '93070',
            cityLabel: 'Saint-Ouen-sur-Seine',
            roadName: 'Rue Ardoin',
            fromHouseNumber: '87',
            toHouseNumber: '63',
        );

        $publishedLocation3 = new Location(
            self::UUID_PUBLISHED3,
            $this->getReference('publishedMeasure', Measure::class),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"MultiLineString","coordinates":[[[2.329780456,48.914583486],[2.330613336,48.914964052],[2.330910397,48.915100608]],[[2.329780456,48.914583486],[2.328950365,48.914199332]],[[2.328950365,48.914199332],[2.328863568,48.914158377]],[[2.325817532,48.912730261],[2.326734253,48.913164386]],[[2.32471204,48.912229415],[2.325817532,48.912730261]],[[2.327532979,48.913544783],[2.328272336,48.91387448],[2.328863568,48.914158377]],[[2.331360722,48.915309063],[2.330910397,48.915100608]],[[2.326734253,48.913164386],[2.327532979,48.913544783]]]}',
        );

        $namedStreetPublishedLocation3 = new NamedStreet(
            uuid: 'c477f85d-0827-454e-932d-d0e1ce8ebe0c',
            location: $publishedLocation3,
            direction: DirectionEnum::BOTH->value,
            cityCode: '93070',
            cityLabel: 'Saint-Ouen-sur-Seine',
            roadName: 'Rue La Clef Des Champs',
            fromHouseNumber: null,
            toHouseNumber: null,
        );

        $publishedLocation4DepartmentalRoad = new Location(
            self::UUID_PUBLISHED4,
            $this->getReference('publishedMeasure', Measure::class),
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD->value,
            geometry: '{"type":"MultiLineString","coordinates":[[[4.663492283,49.820771105],[4.663561067,49.820708163],[4.663623202,49.820654298],[4.663725129,49.820585513],[4.66385317,49.820508284],[4.663996569,49.820433542],[4.664156393,49.820351386],[4.664302802,49.820283791],[4.664436862,49.820220858],[4.664595794,49.82015399],[4.664760103,49.820081657],[4.664970675,49.819979937],[4.66510358,49.819924207],[4.66516665,49.819899084],[4.665248891,49.819865612],[4.665391601,49.819812444],[4.665544028,49.819759143],[4.665691049,49.819710408],[4.665836593,49.819658998],[4.66598766,49.819606614],[4.666138405,49.819544349],[4.66630138,49.819473829]]]}',
        );

        // TODO
        $numberedRoadPublishedLocation4DepartmentalRoad = new NumberedRoad(
            uuid: '1346b8e4-e768-4370-90c3-2c3f8985b9d8',
            location: $publishedLocation4DepartmentalRoad,
            direction: DirectionEnum::BOTH->value,
            administrator: 'Ardennes',
            roadNumber: 'D322',
            fromPointNumber: '1',
            toPointNumber: '4',
            fromSide: 'U',
            toSide: 'U',
        );

        $permanentRegulationOrderLocation = new Location(
            self::UUID_PERMANENT_ONLY_ONE,
            $this->getReference('permanentMeasure', Measure::class),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"MultiLineString","coordinates":[[[2.350482535,48.902835632],[2.35178288,48.902700585]],[[2.348448775,48.903039522],[2.350482535,48.902835632]]]}',
        );

        $namedStreetPermanentRegulationOrderLocation = new NamedStreet(
            uuid: '5a2f6655-5903-4b57-97be-fe6906f8aa42',
            location: $permanentRegulationOrderLocation,
            direction: DirectionEnum::BOTH->value,
            cityCode: '93070',
            cityLabel: 'Saint-Ouen-sur-Seine',
            roadName: 'Rue des Graviers',
            fromHouseNumber: null,
            toHouseNumber: null,
        );

        $fullCityLocation = new Location(
            self::UUID_FULL_CITY,
            $this->getReference('fullCityMeasure', Measure::class),
            roadType: RoadTypeEnum::LANE->value,
            geometry: null,
        );

        $namedStreetFullCityLocation = new NamedStreet(
            uuid: 'be75b5f0-e1e3-4162-b1af-3dff1946ca36',
            location: $fullCityLocation,
            direction: DirectionEnum::BOTH->value,
            cityCode: '75118',
            cityLabel: 'Paris 18e Arrondissement (75018)',
            roadName: null,
            fromHouseNumber: null,
            toHouseNumber: null,
        );

        $cifsLocation = new Location(
            self::UUID_CIFS_NAMED_STREET,
            $this->getReference('cifsMeasure', Measure::class),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"MultiLineString","coordinates":[[[2.339150682,48.911532934],[2.339535452,48.911316524]],[[2.338854183,48.911459354],[2.338947542,48.911521022],[2.339005897,48.911545625],[2.339044051,48.911549433],[2.339090471,48.911546991],[2.339150682,48.911532934]],[[2.337514404,48.910599408],[2.337678265,48.910695641],[2.338854183,48.911459354]]]}',
        );

        $outDatedCifsLocation = new Location(
            self::UUID_OUTDATED_CIFS,
            $this->getReference('outDatedCifsMeasure', Measure::class),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"LineString","coordinates":[[1.35643852,44.01573612],[1.35634358,44.01578421],[1.35628051,44.01580846],[1.35620232,44.01583789],[1.35573093,44.01600635],[1.35515052,44.01623528],[1.3550483,44.01627605],[1.35476043,44.01639595],[1.35431163,44.01660254],[1.354256366,44.016628823]]}',
        );

        $namedStreetCifsLocation = new NamedStreet(
            uuid: '72b70089-04ea-4ba0-8d9e-61ca86fc2413',
            location: $cifsLocation,
            direction: DirectionEnum::BOTH->value,
            cityCode: '93070',
            cityLabel: 'Saint-Ouen-sur-Seine',
            roadName: 'Rue Claude Monet',
            fromHouseNumber: null,
            toHouseNumber: '33',
        );

        $cifsLocationDepartmentalRoad = new Location(
            self::UUID_CIFS_DEPARTMENTAL_ROAD,
            $this->getReference('cifsMeasure', Measure::class),
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD->value,
            geometry: '{"coordinates":[[2.3442412829312502,48.911986097418065],[2.344182729346983,48.90884583118108]],"type":"LineString"}',
        );

        $numberedRoadCifsLocationDepartmentalRoad = new NumberedRoad(
            uuid: 'eb1f2403-8aaf-4a02-8d50-0b0dbc66f85c',
            location: $cifsLocationDepartmentalRoad,
            direction: DirectionEnum::BOTH->value,
            administrator: 'Seine-Saint-Denis',
            roadNumber: 'D14',
            fromPointNumber: '1',
            toPointNumber: '4',
            fromSide: 'U',
            toSide: 'U',
        );

        $rawGeoJSONLocation = new Location(
            '06672bc8-5e45-7e76-8000-36b5483dda9f',
            $this->getReference('rawGeoJSONMeasure', Measure::class),
            roadType: RoadTypeEnum::RAW_GEOJSON->value,
            geometry: '{"type": "Point", "coordinates": [4.8, 49.6]}',
        );

        $rawGeoJSON = new RawGeoJSON(
            uuid: '06672bc8-d38a-72b4-8000-69857847b68f',
            location: $rawGeoJSONLocation,
            label: 'Zone Olympique',
        );

        $litteralisLocation1 = new Location(
            self::UUID_LITTERALIS,
            $this->getReference('litteralisMeasure', Measure::class),
            roadType: RoadTypeEnum::RAW_GEOJSON->value,
            geometry: '{"type":"MultiLineString","coordinates":[[[3.023890325,50.570177599],[3.023850386,50.570151247]],[[3.023890325,50.570177599],[3.024458944,50.570503315]],[[3.024458944,50.570503315],[3.024500711,50.570527945]],[[3.024500711,50.570527945],[3.024501619,50.570528488]],[[3.024501619,50.570528488],[3.025116052,50.570901355]],[[3.025116052,50.570901355],[3.02515503,50.570929555]],[[3.023850386,50.570151247],[3.02384667,50.570148629]],[[3.023475742,50.569868822],[3.023440948,50.569835923]],[[3.023475742,50.569868822],[3.02384667,50.570148629]],[[3.02515503,50.570929555],[3.025159053,50.570932711]],[[3.025159053,50.570932711],[3.025653937,50.571355649]],[[3.025653937,50.571355649],[3.02569009,50.57138952]],[[3.02569009,50.57138952],[3.025691455,50.571390856]],[[3.025691455,50.571390856],[3.026131049,50.571842058]],[[3.026131049,50.571842058],[3.026159516,50.571877523]],[[3.023440948,50.569835923],[3.02343789,50.569832708]],[[3.023149663,50.569492048],[3.023119275,50.569455721]],[[3.023149663,50.569492048],[3.02343789,50.569832708]],[[3.022717354,50.568969715],[3.023119183,50.56945561]],[[3.023119275,50.569455721],[3.023119183,50.56945561]],[[3.026159516,50.571877523],[3.02616073,50.571879188]],[[3.02616073,50.571879188],[3.027150974,50.57338937]]]}',
        );

        $litteralisLocation1RawGeoJSON = new RawGeoJSON(
            uuid: '066e9850-3f1e-7735-8000-dacea1fe4ff1',
            location: $litteralisLocation1,
            label: 'ROUTE 147 (NOYELLES-LÈS-SECLIN) DU PR7 +779 AU PR8 +413',
        );

        $winterMaintenanceLocation = new Location(
            '6bb721d0-bac6-42b5-b092-16f72bba27f7',
            $this->getReference('winterMaintenanceMeasure', Measure::class),
            roadType: RoadTypeEnum::NATIONAL_ROAD->value,
            geometry: '{"type":"LineString","coordinates":[[-2.201030047,48.425146243],[-2.202297854,48.425049341],[-2.203494689,48.424960714],[-2.204844298,48.424855445],[-2.20605839,48.424763329],[-2.207220768,48.424682489],[-2.208240455,48.424597091],[-2.209135407,48.424537907],[-2.209826241,48.424496709],[-2.210661899,48.424481594],[-2.211431991,48.424488292],[-2.212205395,48.424514674],[-2.212921594,48.424565207],[-2.213689115,48.424641427],[-2.214594712,48.424757547],[-2.215491296,48.424905612],[-2.215554944,48.424919039],[-2.215680624,48.424943259],[-2.215978733,48.424998656],[-2.216104502,48.425023774],[-2.21676327,48.425177793],[-2.217372772,48.425326766],[-2.218186595,48.425555094],[-2.219502576,48.425942518],[-2.219780534,48.426026744],[-2.219883914,48.426058253],[-2.219938302,48.426073889],[-2.219946751,48.426077123],[-2.220038895,48.426104619],[-2.220177245,48.42614721],[-2.220368545,48.426204599],[-2.220522179,48.426251024],[-2.221544411,48.42654498],[-2.222444316,48.426780305],[-2.223233216,48.426961914],[-2.223998672,48.427112092],[-2.224057921,48.427122102],[-2.224949811,48.427263094],[-2.225597801,48.427335494],[-2.226444504,48.427417158],[-2.227360371,48.427472322],[-2.228360141,48.427512956],[-2.229256778,48.427551823],[-2.229803018,48.427568284],[-2.229915773,48.427571421],[-2.229968645,48.427571792],[-2.23012591,48.427572964],[-2.230357627,48.427573554],[-2.231164997,48.42757307],[-2.232126589,48.427543229],[-2.23290943,48.427500539],[-2.233736158,48.427436069],[-2.234630872,48.42734695],[-2.235478477,48.427233759],[-2.236366909,48.427095315],[-2.236939988,48.426987034],[-2.237769619,48.426816015],[-2.238652671,48.426596645],[-2.238806891,48.42655376],[-2.238871456,48.426535578],[-2.238900502,48.426527982],[-2.238917587,48.426522718],[-2.239121984,48.426466794],[-2.239360552,48.426400343],[-2.239966013,48.426209468],[-2.240233538,48.426120996],[-2.240654293,48.425979763],[-2.2414583,48.425701655],[-2.242373085,48.425379869],[-2.243183904,48.425115873],[-2.243776118,48.424928268],[-2.244414991,48.424746709],[-2.245340337,48.42449025],[-2.246279517,48.424250302],[-2.247178492,48.42404188],[-2.248091483,48.423851765],[-2.249345596,48.423618576],[-2.251348303,48.42325842],[-2.25248712,48.42306457],[-2.252912128,48.42299343],[-2.252999881,48.422976915],[-2.253983604,48.422803527]]}',
        );

        $winterMaintenanceLocationNumberedRoad = new NumberedRoad(
            uuid: 'c2d9f7b8-3cca-4fc4-b05a-7e4a9b48d1ad',
            location: $winterMaintenanceLocation,
            direction: DirectionEnum::BOTH->value,
            administrator: 'DIR Ouest',
            roadNumber: 'N176',
            fromPointNumber: '24',
            fromSide: 'D',
            fromAbscissa: 0,
            toPointNumber: '28',
            toSide: 'D',
            toAbscissa: 0,
        );

        $parkingProhibitedLocation = new Location(
            '814c51a5-3d55-46de-9185-c3430a5deacc',
            $this->getReference('parkingProhibitedMeasure', Measure::class),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"MultiLineString","coordinates":[[[1.34352783,44.01741201],[1.34351021,44.01728842],[1.34344305,44.01672388]],[[1.34361127,44.01827476],[1.34363309,44.01855416],[1.34367982,44.01909228],[1.34373623,44.01964046],[1.34376444,44.02004327]],[[1.34355908,44.01762403],[1.34352783,44.01741201]],[[1.34361127,44.01827476],[1.34359579,44.01799187],[1.34355908,44.01762403]]]}',
        );

        $manager->persist($namedStreetTypicalMeasureLocation1);
        $manager->persist($namedStreetTypicalMeasureLocation2);
        $manager->persist($namedStreetTypicalMeasureLocation3);
        $manager->persist($namedStreetTypicalMeasureToRemoveLocation1);
        $manager->persist($namedStreetPublishedLocation1);
        $manager->persist($namedStreetPublishedLocation2);
        $manager->persist($namedStreetPublishedLocation3);
        $manager->persist($numberedRoadPublishedLocation4DepartmentalRoad);
        $manager->persist($namedStreetPermanentRegulationOrderLocation);
        $manager->persist($namedStreetFullCityLocation);
        $manager->persist($namedStreetCifsLocation);
        $manager->persist($numberedRoadCifsLocationDepartmentalRoad);
        $manager->persist($outDatedCifsLocation);

        $manager->persist($typicalMeasureLocation1);
        $manager->persist($typicalMeasureLocation2);
        $manager->persist($typicalMeasureLocation3);
        $manager->persist($typicalMeasureToRemoveLocation1);
        $manager->persist($publishedLocation1);
        $manager->persist($publishedLocation2);
        $manager->persist($publishedLocation3);
        $manager->persist($publishedLocation4DepartmentalRoad);
        $manager->persist($permanentRegulationOrderLocation);
        $manager->persist($fullCityLocation);
        $manager->persist($cifsLocation);
        $manager->persist($cifsLocationDepartmentalRoad);
        $manager->persist($rawGeoJSONLocation);
        $manager->persist($rawGeoJSON);
        $manager->persist($litteralisLocation1);
        $manager->persist($litteralisLocation1RawGeoJSON);
        $manager->persist($winterMaintenanceLocation);
        $manager->persist($winterMaintenanceLocationNumberedRoad);
        $manager->persist($parkingProhibitedLocation);
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
