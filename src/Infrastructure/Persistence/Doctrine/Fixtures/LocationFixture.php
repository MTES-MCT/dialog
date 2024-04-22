<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Location\NumberedRoad;
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
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"LineString","coordinates":[[-1.935821678,47.347000002],[-1.9357484,47.34702236],[-1.9353862,47.34710992],[-1.93490428,47.34722332],[-1.93424563,47.34737761],[-1.93374519,47.34750623],[-1.93340389,47.34759019],[-1.93299607,47.34768691],[-1.93291188,47.34770943],[-1.93268153,47.34777334],[-1.93248952,47.34782119],[-1.93234592,47.34784714],[-1.93207756,47.34785854],[-1.93140947,47.3478671],[-1.93097041,47.347872202]]}',
        );

        $namedStreetTypicalMeasureLocation1 = new NamedStreet(
            uuid: '047d007c-f468-4f80-9f8c-11cd19cfe0c1',
            location: $typicalMeasureLocation1,
            cityCode: '44195',
            cityLabel: 'Savenay (44260)',
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: '15',
            toHouseNumber: '37bis',
        );

        $typicalMeasureLocation2 = new Location(
            '34247125-38f4-4e69-b5d7-5516a577d149',
            $this->getReference('typicalMeasure'),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"MultiLineString","coordinates":[[[-1.9348411,47.35982668],[-1.93457085,47.35974617],[-1.93355284,47.35943046],[-1.9331983,47.35932827],[-1.93304583,47.35928695]],[[-1.93523407,47.35991369],[-1.9351106,47.3598991],[-1.93495184,47.35986165],[-1.9348411,47.35982668]],[[-1.93523407,47.35991369],[-1.93540225,47.35992368],[-1.93549671,47.35992598]],[[-1.93732403,47.36010712],[-1.93756453,47.36015281],[-1.93789122,47.36021197],[-1.93794966,47.36024106],[-1.9379971,47.3602661]],[[-1.93658304,47.35997448],[-1.93660982,47.35997695],[-1.93675064,47.35999261],[-1.93703461,47.36004908],[-1.93732403,47.36010712]],[[-1.93549671,47.35992598],[-1.93623338,47.35995418],[-1.93638685,47.35996298]],[[-1.93638685,47.35996298],[-1.93657767,47.35997381],[-1.93658304,47.35997448]]]}',
        );

        $namedStreetTypicalMeasureLocation2 = new NamedStreet(
            uuid: 'b291d4ef-9a78-43c4-9f4f-babc256ac320',
            location: $typicalMeasureLocation2,
            cityCode: '44195',
            cityLabel: 'Savenay (44260)',
            roadName: 'Rue Victor Hugo',
            fromHouseNumber: null,
            toHouseNumber: null,
        );

        $typicalMeasureLocation3 = new Location(
            '0b5d0ddf-f7aa-4f0a-af12-1f654a505200',
            $this->getReference('typicalMeasureToRemove'),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"MultiLineString","coordinates":[[[-1.93900561,47.34611977],[-1.93881261,47.34614242],[-1.93862895,47.34618]],[[-1.94156143,47.34627613],[-1.94141949,47.34627676],[-1.94107,47.3462718],[-1.94073762,47.34626521],[-1.94044736,47.34624059],[-1.94002252,47.34619554],[-1.93967674,47.34615885],[-1.93940971,47.34612692],[-1.93920009,47.3461133],[-1.93900561,47.34611977]],[[-1.94782475,47.34625455],[-1.94772148,47.34625715],[-1.94736744,47.34624609],[-1.94687427,47.34622292],[-1.94662516,47.34621279],[-1.94604524,47.3461843],[-1.94554728,47.34616674],[-1.94501983,47.34616036]],[[-1.93862895,47.34618],[-1.93856253,47.34619365],[-1.93810681,47.34628881],[-1.93776551,47.34637279],[-1.93753691,47.34644115],[-1.93732707,47.34654028]],[[-1.94406201,47.34619306],[-1.94356307,47.346208],[-1.9430443,47.34622379],[-1.94264103,47.34624007],[-1.94217406,47.34625635],[-1.941718,47.34627578],[-1.94156143,47.34627613]],[[-1.94501983,47.34616036],[-1.94455905,47.34617188],[-1.94406201,47.34619306]],[[-1.93625155,47.34686565],[-1.93618471,47.34688924],[-1.9357484,47.34702236]],[[-1.92909849,47.34789133],[-1.92841676,47.34789593],[-1.92816427,47.34789223]],[[-1.9357484,47.34702236],[-1.9353862,47.34710992],[-1.93490428,47.34722332],[-1.93424563,47.34737761],[-1.93374519,47.34750623],[-1.93340389,47.34759019],[-1.93299607,47.34768691],[-1.93291188,47.34770943]],[[-1.9299491,47.34789037],[-1.92909849,47.34789133]],[[-1.93291188,47.34770943],[-1.93268153,47.34777334],[-1.93248952,47.34782119],[-1.93234592,47.34784714],[-1.93207756,47.34785854],[-1.93140947,47.3478671],[-1.93070816,47.34787525],[-1.93059414,47.34787649],[-1.9299491,47.34789037]],[[-1.93732707,47.34654028],[-1.93719248,47.34653428],[-1.9369894,47.3465339]],[[-1.9369894,47.3465339],[-1.93693099,47.34659141],[-1.93684045,47.34664577],[-1.93673693,47.34670339],[-1.93656674,47.34675754],[-1.93625155,47.34686565]]]}',
        );

        $namedStreetTypicalMeasureLocation3 = new NamedStreet(
            uuid: 'b640220b-a80b-45b7-915c-b1fc878ada9a',
            location: $typicalMeasureLocation3,
            cityCode: '44195',
            cityLabel: 'Savenay (44260)',
            roadName: 'Route du Grand Brossais',
            fromHouseNumber: null,
            toHouseNumber: null,
        );

        $publishedLocation1 = new Location(
            self::UUID_PUBLISHED,
            $this->getReference('publishedMeasure'),
            roadType: RoadTypeEnum::LANE->value,
            geometry: GeoJSON::toLineString([
                Coordinates::fromLonLat(1.362275, 44.028996),
                Coordinates::fromLonLat(1.35931, 44.025665),
            ]),
        );

        $namedStreetPublishedLocation1 = new NamedStreet(
            uuid: 'e998d4bc-2e4a-4686-9105-e06869ddb24b',
            location: $publishedLocation1,
            cityCode: '82121',
            cityLabel: 'Montauban (82000)',
            roadName: 'Avenue de Fonneuve',
            fromHouseNumber: '695',
            toHouseNumber: '253',
        );

        $publishedLocation2 = new Location(
            '064ca782-771c-783f-8000-e67473eabea6',
            $this->getReference('publishedMeasure'),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"LineString","coordinates":[[1.352105256,44.016810494],[1.35213207,44.01678578],[1.35216909,44.01675663],[1.35221741,44.01672495],[1.35226814,44.01669601],[1.35232384,44.01666804],[1.35272189,44.0164913],[1.35280254,44.01646281],[1.35301215,44.016390795]]}',
        );

        $namedStreetPublishedLocation2 = new NamedStreet(
            uuid: '1578630f-06d7-457a-bfde-8447be156107',
            location: $publishedLocation2,
            cityCode: '82121',
            cityLabel: 'Montauban (82000)',
            roadName: "Rue de l'Hôtel de Ville",
            fromHouseNumber: '30',
            toHouseNumber: '12',
        );

        $publishedLocation3 = new Location(
            '0655b3f6-124a-7f8d-8000-7c747883d40d',
            $this->getReference('publishedMeasure'),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"MultiLineString","coordinates":[[[1.34352783,44.01741201],[1.34351021,44.01728842],[1.34344305,44.01672388]],[[1.34361127,44.01827476],[1.34363309,44.01855416],[1.34367982,44.01909228],[1.34373623,44.01964046],[1.34376444,44.02004327]],[[1.34355908,44.01762403],[1.34352783,44.01741201]],[[1.34361127,44.01827476],[1.34359579,44.01799187],[1.34355908,44.01762403]]]}',
        );

        $namedStreetPublishedLocation3 = new NamedStreet(
            uuid: 'c477f85d-0827-454e-932d-d0e1ce8ebe0c',
            location: $publishedLocation3,
            cityCode: '82121',
            cityLabel: 'Montauban (82000)',
            roadName: 'Rue Gamot',
            fromHouseNumber: null,
            toHouseNumber: null,
        );

        $publishedLocation4DepartmentalRoad = new Location(
            '065f9436-0ff3-74e3-8000-d9ac9b5a16ad',
            $this->getReference('publishedMeasure'),
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD->value,
            geometry: '{"type":"MultiLineString","coordinates":[[[4.663492283,49.820771105],[4.663561067,49.820708163],[4.663623202,49.820654298],[4.663725129,49.820585513],[4.66385317,49.820508284],[4.663996569,49.820433542],[4.664156393,49.820351386],[4.664302802,49.820283791],[4.664436862,49.820220858],[4.664595794,49.82015399],[4.664760103,49.820081657],[4.664970675,49.819979937],[4.66510358,49.819924207],[4.66516665,49.819899084],[4.665248891,49.819865612],[4.665391601,49.819812444],[4.665544028,49.819759143],[4.665691049,49.819710408],[4.665836593,49.819658998],[4.66598766,49.819606614],[4.666138405,49.819544349],[4.66630138,49.819473829]]]}',
        );

        $numberedRoadPublishedLocation4DepartmentalRoad = new NumberedRoad(
            uuid: '1346b8e4-e768-4370-90c3-2c3f8985b9d8',
            location: $publishedLocation4DepartmentalRoad,
            administrator: 'Ardennes',
            roadNumber: 'D322',
        );

        $permanentRegulationOrderLocation = new Location(
            self::UUID_PERMANENT_ONLY_ONE,
            $this->getReference('permanentMeasure'),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"MultiLineString","coordinates":[[[2.35263563,48.89325427],[2.35117787,48.89356294],[2.35100967,48.893598]],[[2.35100967,48.893598],[2.3492403,48.89406594]],[[2.3457314,48.8950073],[2.3463186,48.89484233]],[[2.3463186,48.89484233],[2.3471424,48.89462649]],[[2.34777601,48.89445727],[2.3492403,48.89406594]],[[2.3471424,48.89462649],[2.34777601,48.89445727]]]}',
        );

        $namedStreetPermanentRegulationOrderLocation = new NamedStreet(
            uuid: '5a2f6655-5903-4b57-97be-fe6906f8aa42',
            location: $permanentRegulationOrderLocation,
            cityCode: '75118',
            cityLabel: 'Paris 18e Arrondissement (75018)',
            roadName: 'Rue du Simplon',
            fromHouseNumber: null,
            toHouseNumber: null,
        );

        $fullCityLocation = new Location(
            self::UUID_FULL_CITY,
            $this->getReference('fullCityMeasure'),
            roadType: RoadTypeEnum::LANE->value,
            geometry: null,
        );

        $namedStreetFullCityLocation = new NamedStreet(
            uuid: 'be75b5f0-e1e3-4162-b1af-3dff1946ca36',
            location: $fullCityLocation,
            cityCode: '75118',
            cityLabel: 'Paris 18e Arrondissement (75018)',
            roadName: null,
            fromHouseNumber: null,
            toHouseNumber: null,
        );

        $cifsLocation = new Location(
            '06548f85-d545-7b45-8000-8a23c45850b3',
            $this->getReference('cifsMeasure'),
            roadType: RoadTypeEnum::LANE->value,
            geometry: '{"type":"LineString","coordinates":[[1.35643852,44.01573612],[1.35634358,44.01578421],[1.35628051,44.01580846],[1.35620232,44.01583789],[1.35573093,44.01600635],[1.35515052,44.01623528],[1.3550483,44.01627605],[1.35476043,44.01639595],[1.35431163,44.01660254],[1.354256366,44.016628823]]}',
        );

        $namedStreetCifsLocation = new NamedStreet(
            uuid: '72b70089-04ea-4ba0-8d9e-61ca86fc2413',
            location: $cifsLocation,
            cityCode: '82121',
            cityLabel: 'Montauban (82000)',
            roadName: 'Rue de la République',
            fromHouseNumber: null,
            toHouseNumber: '33',
        );

        $cifsLocationDepartmentalRoad = new Location(
            '065f94ef-ea0a-7ab5-8000-bd5686102151',
            $this->getReference('cifsMeasure'),
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD->value,
            geometry: '{"type":"MultiLineString","coordinates":[[[4.813813598,49.591846975],[4.81409852,49.591849011],[4.81432125,49.591851976],[4.81453861,49.591859516],[4.814740991,49.591873571],[4.815013014,49.591901865],[4.815340858,49.591944606],[4.81583431,49.59201723],[4.816153896,49.592060991],[4.816495277,49.592095437],[4.816667381,49.592113537],[4.817041455,49.592134008],[4.817146808,49.592141421],[4.817453002,49.592158412],[4.817759229,49.592176302],[4.818021167,49.592193055],[4.818187712,49.592210337],[4.818337896,49.592234157],[4.818555532,49.592288427],[4.818869804,49.592377205],[4.819164345,49.592455491],[4.819289813,49.592484173],[4.819453914,49.592510479],[4.819596743,49.592521823],[4.819736616,49.592527817],[4.819889992,49.59252462],[4.820079014,49.592512798],[4.820292499,49.592488924],[4.820544363,49.592455486],[4.820994157,49.592388517],[4.821688805,49.592285514],[4.822331284,49.592193178],[4.822782488,49.59212708],[4.822841628,49.592118103],[4.823690483,49.591996594],[4.824917507,49.591816362],[4.826338589,49.591610728],[4.827469365,49.5914463],[4.828271465,49.591332653],[4.829012881,49.591223511],[4.829516231,49.591146714],[4.829814141,49.591086495],[4.829968258,49.591026644],[4.83008609,49.590957451],[4.830170433,49.590879775],[4.830240539,49.590790627],[4.830268023,49.590747066],[4.830350572,49.590619078],[4.830489918,49.590416525],[4.830621002,49.590214996],[4.830722512,49.590075935],[4.830817075,49.589974732],[4.830910737,49.589887027],[4.831041199,49.589784384],[4.831181718,49.589692376],[4.831403969,49.589565876],[4.831660833,49.589440652],[4.832030665,49.589265183],[4.832392396,49.589095228],[4.832750204,49.588931623],[4.833139023,49.588745976],[4.833546609,49.588543864],[4.834215703,49.588230832],[4.834599144,49.588088408],[4.834701471,49.588050007],[4.835578995,49.587727527],[4.836436455,49.587424219],[4.836971699,49.587235446],[4.83719338,49.587170967],[4.837376079,49.587137641],[4.837622313,49.587102453],[4.837965376,49.587068497],[4.838159709,49.587051173],[4.838247957,49.587044444],[4.838472209,49.587013183],[4.838689132,49.586970347],[4.83903415,49.586875233],[4.839237347,49.586796648],[4.83945359,49.586696292],[4.840037366,49.586396205],[4.840868245,49.585892813],[4.841301095,49.58562557],[4.841521567,49.58548919],[4.841639596,49.585426275],[4.841813443,49.585339142],[4.841909974,49.585293631],[4.841994198,49.585251903],[4.842452242,49.585070568],[4.842908002,49.584902749],[4.843561098,49.584686088],[4.844292023,49.584480826],[4.844969502,49.584288956],[4.84528843,49.584199613],[4.846238524,49.583937973],[4.846801113,49.583781099],[4.847011939,49.58372306],[4.847686829,49.583536607],[4.849179902,49.583112069],[4.849655173,49.582987972],[4.850053105,49.582904604],[4.850228494,49.582899235],[4.850304524,49.582898976],[4.850361339,49.582902604],[4.85044431,49.582903138],[4.850490136,49.582908732],[4.850601585,49.582932202],[4.850659879,49.582938505],[4.850876128,49.582992739],[4.851116666,49.583030422],[4.851311595,49.583030147],[4.851491993,49.583010317],[4.851669076,49.582975256],[4.851880087,49.582922598],[4.85221904,49.582814054],[4.852531382,49.582696027],[4.852786076,49.582589666],[4.853172823,49.582426456],[4.853492639,49.58228584],[4.853555324,49.582260612],[4.853658716,49.582214089],[4.853868132,49.582117405],[4.854309739,49.581903914],[4.854663703,49.581751986],[4.855219612,49.581564611],[4.855401273,49.581503406],[4.855751522,49.58140187],[4.856102128,49.581310216],[4.856392327,49.581230272],[4.857172925,49.581025069],[4.857185133,49.58101859],[4.857518763,49.580916405],[4.85777087,49.580815465],[4.858005811,49.580698608],[4.858136813,49.580613005],[4.858303787,49.580489996],[4.858477782,49.580331821],[4.858741293,49.580087779],[4.85898159,49.579889936],[4.859253989,49.579700589],[4.85950706,49.579550192],[4.859780092,49.579416567],[4.859859983,49.579370397],[4.860017849,49.57930146],[4.860119652,49.579249561],[4.86019852,49.579213295],[4.860543702,49.579048899],[4.861152737,49.578726744],[4.862031578,49.578220665],[4.862692326,49.57783838],[4.863505569,49.577353971],[4.863674717,49.577253393],[4.864052926,49.577009375],[4.864288608,49.57683766],[4.864752666,49.576483552],[4.864991237,49.576315386],[4.865182723,49.576183001],[4.865404244,49.576040265],[4.865646847,49.575907094],[4.866017326,49.575717122],[4.86721799,49.575130547],[4.867717907,49.574889139],[4.867984573,49.574771773],[4.868325449,49.574642477],[4.86878396,49.574479008],[4.869179407,49.574329992],[4.869317143,49.574278431],[4.869446852,49.574234185],[4.869570707,49.574181039],[4.869656828,49.574154543],[4.869863647,49.574064163],[4.870047772,49.573957951],[4.87023595,49.573811225],[4.870349249,49.57373397],[4.870446383,49.573554488],[4.87051322,49.573378169],[4.870590796,49.573117186],[4.870681327,49.572870386],[4.870824578,49.57266682],[4.87101305,49.572490424],[4.87127066,49.57231476],[4.871492835,49.57219088],[4.871846982,49.572009231],[4.872238099,49.571818022],[4.872550796,49.57163701],[4.872740733,49.57150104],[4.872927518,49.571316576],[4.873072333,49.571118377],[4.873109609,49.571041393],[4.873159951,49.570905778],[4.873270663,49.570644281],[4.873345767,49.570543342],[4.873398102,49.570500285],[4.873407477,49.57049205],[4.873461259,49.570450768],[4.873610828,49.570345084],[4.87385572,49.570200173],[4.874025152,49.570108564],[4.874322174,49.569952959],[4.874562651,49.569838678],[4.874742902,49.569740608],[4.874886019,49.569647607],[4.875011836,49.569535096],[4.875113677,49.569409471],[4.875185749,49.569301386],[4.875258088,49.569162733],[4.875310987,49.569021683],[4.875386622,49.568784098],[4.875457325,49.568525014],[4.875547372,49.568190121],[4.875613794,49.567965262],[4.875670018,49.567763933],[4.87573956,49.567586669],[4.875814693,49.567411116],[4.875909131,49.567234366],[4.876066525,49.566964952],[4.876148691,49.566830641],[4.876222435,49.566730619],[4.876294236,49.5666531],[4.876363964,49.566594491],[4.876449182,49.566543733],[4.87652815,49.566511049],[4.876644742,49.566486774],[4.876749592,49.566481557],[4.876871641,49.566493154],[4.877059828,49.566536988],[4.877664876,49.566679549],[4.878210136,49.566812245],[4.878405363,49.566859564],[4.878462425,49.566870367],[4.878555698,49.5668887],[4.878647359,49.566900765],[4.878777665,49.566911333],[4.878899486,49.566916639],[4.879036074,49.56691003],[4.87920953,49.566891164],[4.879470406,49.566843976],[4.879967488,49.56671762],[4.880202972,49.566656441],[4.880579284,49.566553527],[4.880724125,49.566508135],[4.880837228,49.566464133],[4.880974307,49.566395488],[4.881110628,49.566306179],[4.881244711,49.566193533],[4.881426479,49.565986658],[4.881630509,49.565746178],[4.881794581,49.565546769],[4.881932605,49.565390912],[4.882047478,49.565282158],[4.882156563,49.565204058],[4.882265058,49.565147541],[4.882396048,49.565101462],[4.882514114,49.565079854],[4.882543022,49.565076709],[4.882650433,49.565066054],[4.882894525,49.56505148],[4.883266978,49.565032218],[4.883664359,49.565014366],[4.884066082,49.565001838],[4.88444429,49.564988775],[4.884772706,49.564974687],[4.884880248,49.564967623],[4.8850042,49.56495581],[4.885164788,49.564925449],[4.885476161,49.564861283],[4.885765467,49.564798358],[4.885971107,49.564752914],[4.886017708,49.564742301],[4.886265342,49.56467373],[4.886538759,49.564592172],[4.886850519,49.564501029],[4.887131891,49.564410357],[4.887448256,49.564331725],[4.887657501,49.564647593],[4.887803303,49.564929388],[4.887909058,49.565061684],[4.887977011,49.565105573],[4.88808331,49.565139877],[4.888214995,49.565150414],[4.88832099,49.565138877],[4.888475919,49.565105004],[4.888636307,49.565069248],[4.888833334,49.565052698],[4.888984026,49.565053948],[4.889138076,49.565071327],[4.889267494,49.565095382],[4.889364125,49.565129835],[4.88940902,49.565148014],[4.889501647,49.56522388],[4.889549246,49.565277974],[4.88957754,49.565333267],[4.889644922,49.56547425],[4.889664367,49.565514399],[4.889719641,49.565626804],[4.889818826,49.56576819],[4.889939096,49.565919136],[4.8900792,49.566083257],[4.890259372,49.566284508],[4.890408287,49.566425119],[4.890531406,49.566503207],[4.890621101,49.566536868],[4.890717107,49.56655425],[4.890867803,49.566555499],[4.891046978,49.56654192],[4.891450415,49.566500576],[4.891658431,49.566482052],[4.891908119,49.566469172],[4.892189511,49.566453998],[4.892599498,49.566440415],[4.893234441,49.566417928],[4.893769862,49.566395193],[4.894331594,49.566373843],[4.894977386,49.566345784],[4.895232631,49.566333708],[4.895453708,49.566332054],[4.895596514,49.566344207],[4.895687162,49.566366163],[4.895749664,49.566411932],[4.895774673,49.566452892],[4.895795549,49.566531672],[4.89578913,49.566619868],[4.89576112,49.56675964],[4.895722966,49.566961597],[4.89562303,49.567324528],[4.895554575,49.567604266],[4.89547277,49.567896797],[4.895411684,49.568076638],[4.895358011,49.568195237],[4.895302161,49.568292294],[4.895218197,49.568414062],[4.895112694,49.568551448],[4.894948499,49.568746382],[4.894840199,49.568882912],[4.89471698,49.569064622],[4.894622073,49.569227011],[4.894544659,49.569376543],[4.894469488,49.569549412],[4.894409733,49.569690577],[4.894341104,49.569853456],[4.894269202,49.570077512],[4.894218132,49.570229329],[4.89407647,49.570698083],[4.894045878,49.570805533],[4.893982229,49.571028561],[4.893954642,49.571180012],[4.893942947,49.571350091],[4.893963214,49.571487311],[4.894030516,49.571625595],[4.89412788,49.57175442],[4.894256421,49.571866578],[4.894465486,49.571988267],[4.894766922,49.572140874],[4.895141357,49.572323804],[4.895365423,49.572439863],[4.895536463,49.572542367],[4.895692705,49.572655889],[4.895801181,49.572748582],[4.895898705,49.572844143],[4.896027762,49.57300753],[4.896312361,49.573340182],[4.89649466,49.573560268],[4.89657351,49.573674099],[4.896618374,49.573765987],[4.896641034,49.573855526],[4.896649248,49.573928211],[4.89663557,49.574044386],[4.896628389,49.574111917],[4.896502553,49.57455975],[4.896236784,49.575523054],[4.89612879,49.575930155],[4.896100037,49.576124772],[4.896095023,49.576288454],[4.89612934,49.576618722],[4.896137026,49.576789397],[4.896138655,49.576945784]]]}',
        );

        $numberedRoadCifsLocationDepartmentalRoad = new NumberedRoad(
            uuid: 'eb1f2403-8aaf-4a02-8d50-0b0dbc66f85c',
            location: $cifsLocationDepartmentalRoad,
            administrator: 'Ardennes',
            roadNumber: 'D324',
        );

        $manager->persist($namedStreetTypicalMeasureLocation1);
        $manager->persist($namedStreetTypicalMeasureLocation2);
        $manager->persist($namedStreetTypicalMeasureLocation3);
        $manager->persist($namedStreetPublishedLocation1);
        $manager->persist($namedStreetPublishedLocation2);
        $manager->persist($namedStreetPublishedLocation3);
        $manager->persist($numberedRoadPublishedLocation4DepartmentalRoad);
        $manager->persist($namedStreetPermanentRegulationOrderLocation);
        $manager->persist($namedStreetFullCityLocation);
        $manager->persist($namedStreetCifsLocation);
        $manager->persist($numberedRoadCifsLocationDepartmentalRoad);

        $manager->persist($typicalMeasureLocation1);
        $manager->persist($typicalMeasureLocation2);
        $manager->persist($typicalMeasureLocation3);
        $manager->persist($publishedLocation1);
        $manager->persist($publishedLocation2);
        $manager->persist($publishedLocation3);
        $manager->persist($publishedLocation4DepartmentalRoad);
        $manager->persist($permanentRegulationOrderLocation);
        $manager->persist($fullCityLocation);
        $manager->persist($cifsLocation);
        $manager->persist($cifsLocationDepartmentalRoad);
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
