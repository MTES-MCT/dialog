<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Adapter;

use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\GeocodingFailureException;
use App\Domain\Geography\Coordinates;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Infrastructure\Adapter\BdTopoRoadGeocoder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BdTopoRoadGeocoderTest extends KernelTestCase
{
    /** @var BdTopoRoadGeocoder */
    private $roadGeocoder;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->roadGeocoder = $container->get(BdTopoRoadGeocoder::class);
    }

    public function testComputeRoadLine(): void
    {
        $roadBanId = '59606_1480'; // Rue de Famars @ Valenciennes
        $geometry = '{"type":"MultiLineString","coordinates":[[[3.521789594,50.354739626],[3.521764707,50.354775643],[3.521598673,50.355004387],[3.521507319,50.355126875]],[[3.521464982,50.355375745],[3.521433481,50.355314828]],[[3.522862085,50.352877527],[3.522829301,50.352963855],[3.522741262,50.353134812]],[[3.523000274,50.352264637],[3.522940617,50.352465999],[3.522934214,50.352525281],[3.522935218,50.35262224]],[[3.52235843,50.356688146],[3.522243056,50.356523439],[3.522150571,50.356400831],[3.522082435,50.356325704],[3.521979012,50.356230975],[3.521923783,50.356182727],[3.521851614,50.356124675]],[[3.522631994,50.357222078],[3.522606363,50.357186275],[3.522603315,50.357162945],[3.522634011,50.357145757],[3.522629615,50.357127819],[3.522454698,50.356905007]],[[3.522454698,50.356905007],[3.522376207,50.356778749],[3.522382825,50.356740116],[3.52235843,50.356688146]],[[3.521507319,50.355126875],[3.521433481,50.355314828]],[[3.522935218,50.35262224],[3.522936816,50.352641087],[3.522922171,50.352717463],[3.522862085,50.352877527]],[[3.522232905,50.354048237],[3.52215435,50.354186831],[3.522034181,50.354372287]],[[3.521851614,50.356124675],[3.521786581,50.356078264],[3.521725582,50.356014778],[3.521658859,50.355940542],[3.521614653,50.355872496],[3.521568534,50.355755079],[3.521531599,50.355575674],[3.52150244,50.355469857],[3.521464982,50.355375745]],[[3.522741262,50.353134812],[3.522596769,50.353410152],[3.52235284,50.353840336]],[[3.52235284,50.353840336],[3.522289434,50.353951035],[3.522232905,50.354048237]],[[3.522034181,50.354372287],[3.521887767,50.354598256],[3.521789594,50.354739626]]]}';

        $this->assertSame($geometry, $this->roadGeocoder->computeRoadLine($roadBanId));
    }

    public function testComputeRoadLineErrorNotFound(): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessageMatches('/no result found/');

        $this->roadGeocoder->computeRoadLine('12345_6789');
    }

    private function provideTestFindRoads(): array
    {
        return [
            'departmentalRoad-administratorDoesNotExist' => [
                'search' => 'D90',
                'roadType' => RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                'administrator' => 'DIR Nord', // Pas un gestionnaire de départementale
                'result' => [],
            ],
            'departmentalRoad-searchNoResults' => [
                'search' => 'blabla search',
                'roadType' => RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                'administrator' => 'Ardèche',
                'result' => [],
            ],
            'departmentalRoad-searchNotD' => [
                'search' => '90',
                'roadType' => RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                'administrator' => 'Ardèche',
                'result' => [],
            ],
            'departmentalRoad-success' => [
                'search' => 'D90',
                'roadType' => RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                'administrator' => 'Ardèche',
                'result' => [
                    ['roadNumber' => 'D901'],
                    ['roadNumber' => 'D902'],
                    ['roadNumber' => 'D906'],
                ],
            ],
            'departmentalRoad-success-RD' => [
                'search' => 'RD90',
                'roadType' => RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                'administrator' => 'Ardèche',
                'result' => [
                    ['roadNumber' => 'D901'],
                    ['roadNumber' => 'D902'],
                    ['roadNumber' => 'D906'],
                ],
            ],
            'nationalRoad-administratorDoesNotExist' => [
                'search' => 'N10',
                'roadType' => RoadTypeEnum::NATIONAL_ROAD->value,
                'administrator' => 'Ardèche', // Pas un gestionnaire de nationale
                'result' => [],
            ],
            'nationalRoad-searchNoResults' => [
                'search' => 'blabla search',
                'roadType' => RoadTypeEnum::NATIONAL_ROAD->value,
                'administrator' => 'DIR Île-de-France',
                'result' => [],
            ],
            'nationalRoad-searchNotN' => [
                'search' => '10',
                'roadType' => RoadTypeEnum::NATIONAL_ROAD->value,
                'administrator' => 'DIR Île-de-France',
                'result' => [],
            ],
            'nationalRoad-success' => [
                'search' => 'N10',
                'roadType' => RoadTypeEnum::NATIONAL_ROAD->value,
                'administrator' => 'DIR Île-de-France',
                'result' => [
                    ['roadNumber' => 'N10'],
                    ['roadNumber' => 'N1013'],
                    ['roadNumber' => 'N1014'],
                    ['roadNumber' => 'N104'],
                    ['roadNumber' => 'N105'],
                ],
            ],
            'nationalRoad-success-RN' => [
                'search' => 'RN11',
                'roadType' => RoadTypeEnum::NATIONAL_ROAD->value,
                'administrator' => 'DIR Île-de-France',
                'result' => [
                    ['roadNumber' => 'N118'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideTestFindRoads
     */
    public function testFindRoads(string $search, string $roadType, string $administrator, array $result): void
    {
        $this->assertEquals($result, $this->roadGeocoder->findRoads($search, $roadType, $administrator));
    }

    private function provideTestComputeRoad(): array
    {
        // Departmental road lines are very long, use hashes
        return [
            'success' => [
                RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                'Ardèche',
                'D906',
                '188aafa16d56b14ac98387d369be5798',
            ],
            'multiple-results' => [
                RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                'Aisne',
                'D60',
                'a443edf6c51aa195a0e7b9c703fce783',
            ],
        ];
    }

    /**
     * @dataProvider provideTestComputeRoad
     */
    public function testComputeRoad(string $roadType, string $administrator, string $roadNumber, string $geometryMd5): void
    {
        $this->assertSame($geometryMd5, md5($this->roadGeocoder->computeRoad($roadType, $administrator, $roadNumber)));
    }

    private function provideTestComputeRoadNoResult(): array
    {
        return [
            'noResult-roadDoesNotExist' => [
                RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                'D9000',
                'Ardèche',
            ],
            'noResult-administratorDoesNotExist' => [
                RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                'D902',
                'La Dèche',
            ],
        ];
    }

    /**
     * @dataProvider provideTestComputeRoadNoResult
     */
    public function testComputeRoadNoResult(string $roadType, string $administrator, string $roadNumber): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessageMatches('/no result found/');

        $this->roadGeocoder->computeRoad($roadType, $administrator, $roadNumber);
    }

    private function provideTestComputeReferencePoint(): array
    {
        return [
            'success' => [
                'D906',
                'Ardèche',
                null,
                '34',
                'U',
                0,
                Coordinates::fromLonLat(3.905822232, 44.592400215),
            ],
            'success-with-departmentCode' => [
                'N12',
                'DIR Ouest',
                '22', // Existe aussi dans le département 29
                '1',
                'D',
                0,
                Coordinates::fromLonLat(-2.149963152, 48.267350044),
            ],
            'abscissa' => [
                'D906',
                'Ardèche',
                null,
                '34',
                'U',
                500,
                Coordinates::fromLonLat(3.905982764, 44.596555055),
            ],
            'pr-and-line-inverted-order' => [
                'D978',
                'Corrèze',
                null,
                '1',
                'U',
                100,
                Coordinates::fromLonLat(2.182019487, 45.221793989),
            ],
            // Test pour vérifier qu'on exclut les entrées de type "DS", "FS" ou "CS"
            // Ici c'est un cas où il y a deux entrées au PR51D : un type "PR" et un type "FS".
            'pr-type-excluded-fs' => [
                'N79',
                'DIR Centre Est',
                null,
                // Ce numéro de PR existe en deux variantes dans point_de_repere :
                // un type_de_pr = 'PR', et un type_de_pr = 'FS' (qu'on ne veut pas).
                '51',
                'D',
                150,
                Coordinates::fromLonLat(4.534703013, 46.374892141),
            ],
        ];
    }

    /**
     * @dataProvider provideTestComputeReferencePoint
     */
    public function testComputeReferencePoint(string $roadNumber, string $administrator, ?string $departmentCode, string $pointNumber, string $side, int $abscissa, Coordinates $coords): void
    {
        $this->assertEquals($coords, $this->roadGeocoder->computeReferencePoint(
            RoadTypeEnum::DEPARTMENTAL_ROAD->value,
            $administrator,
            $roadNumber,
            $departmentCode,
            $pointNumber,
            $side,
            $abscissa,
        ));
    }

    public function testComputeReferencePointErrorNoResult(): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessageMatches('/no result found/');

        $this->roadGeocoder->computeReferencePoint(
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD->value,
            administrator: 'Ardèche',
            roadNumber: 'D906',
            departmentCode: null,
            pointNumber: '1',
            side: 'U',
            abscissa: 0,
        );
    }

    public function testComputeReferencePointErrorOutOfRange(): void
    {
        $this->expectException(AbscissaOutOfRangeException::class);

        $this->roadGeocoder->computeReferencePoint(
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD->value,
            administrator: 'Ardèche',
            roadNumber: 'D906',
            departmentCode: null,
            pointNumber: '34',
            side: 'U',
            abscissa: 4000,
        );
    }

    private function provideTestFindIntersectingRoadNames(): array
    {
        return [
            'no-result' => ['12345_6789', '59606', []],
            'success' => [
                // Rue des Récollets @ Valenciennes
                '59606_3210',
                '59606',
                [
                    ['roadBanId' => '59606_3980', 'roadName' => 'Rue de la Vieille Poissonnerie'],
                    ['roadBanId' => '59606_2840', 'roadName' => 'Rue de Paris'],
                    ['roadBanId' => '59606_2700', 'roadName' => 'Rue des Moulineaux'],
                    ['roadBanId' => '59606_3560', 'roadName' => 'Rue des Sayneurs'],
                    ['roadBanId' => '59606_1770', 'roadName' => 'Rue Georges Chastelain'],
                    ['roadBanId' => '59606_3430', 'roadName' => 'Rue Saint-jean'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideTestFindIntersectingRoadNames
     */
    public function testFindIntersectingRoads(string $roadBanId, string $cityCode, array $roadNames): void
    {
        $this->assertEquals($roadNames, $this->roadGeocoder->findIntersectingNamedStreets($roadBanId, $cityCode));
    }

    public function testComputeIntersection(): void
    {
        $roadBanId = '93070_4105'; // Avenue Gabriel Péri @ Saint-Ouen
        $otherRoadBanId = '93070_0061'; // Rue Anslme @ Saint-Ouen
        $coords = Coordinates::fromLonLat(2.333447271825283, 48.91081336437863);

        $this->assertEquals($coords, $this->roadGeocoder->computeIntersection($roadBanId, $otherRoadBanId));
    }

    private function provideTestComputeIntersectionError(): array
    {
        return [
            'roadBanId-does-not-exist' => ['12345_6789', '93070_0061'],
            'otherRoadBanId-does-not-exist' => ['93070_0061', '12345_6789'],
            'roads-do-not-intersect' => ['59606_1480', '93070_0061'],
        ];
    }

    /**
     * @dataProvider provideTestComputeIntersectionError
     */
    public function testComputeIntersectionError(string $roadBanId, string $otherRoadBanId): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->roadGeocoder->computeIntersection($roadBanId, $otherRoadBanId);
    }

    public function testFindSectionsInArea(): void
    {
        // This area is a rectangle, it contains:
        // * A portion of Rue de Vaudherlant near Le Fayel (60680).
        // * A portion of Autoroute A1 (highway)
        // * A portion of "bretelle" (access to A1 from Rue de Vaudherlant)
        $northWest = [2.70299, 49.375841];
        $northEast = [2.705149, 49.375841];
        $southEast = [2.705149, 49.374397];
        $southWest = [2.70299, 49.374397];

        $area = json_encode([
            'type' => 'Polygon',
            'crs' => ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']],
            'coordinates' => [
                // Note: according to GeoJSON standard, a polygon is made of "linear rings"
                // which must be closed (end point = start point)
                [$northWest, $northEast, $southEast, $southWest, $northWest],
            ],
        ]);

        // By default all types of sections are included.
        $geometry = $this->roadGeocoder->findSectionsInArea($area);
        $this->assertSame(
            '{"type":"MultiLineString","coordinates":[[[2.704047618,49.379991827],[2.704002207,49.37998812],[2.703927917,49.37997895],[2.703874323,49.37996174],[2.703838642,49.379941878],[2.703823638,49.379917572],[2.703819668,49.379889697],[2.703829635,49.379832194],[2.703868123,49.379603078],[2.703908085,49.379356887],[2.704045651,49.378176115],[2.704074021,49.377790571],[2.704148908,49.377215479],[2.704347775,49.376145415],[2.70449489,49.375697239],[2.704644914,49.375221205],[2.704661989,49.375124169],[2.704663706,49.375064848]],[[2.703813123,49.376475788],[2.703820301,49.376424571],[2.703926664,49.37516282]],[[2.703926664,49.37516282],[2.703928546,49.375074735],[2.703931583,49.375025305]],[[2.702812091,49.375080986],[2.703003456,49.37507786],[2.703226487,49.375073913],[2.703234746,49.375073934],[2.703444002,49.37507175],[2.703720699,49.37507153],[2.703928546,49.375074735],[2.704122628,49.375077907],[2.70422448,49.375080853],[2.704291927,49.375081917],[2.704411689,49.375082209],[2.704450238,49.375081405],[2.704547996,49.375078048],[2.704663706,49.375064848]],[[2.704663706,49.375064848],[2.705508168,49.374958144],[2.706102966,49.374938917],[2.706678394,49.374936719],[2.707757531,49.37495641]],[[2.704141355,49.370375059],[2.704154607,49.371424076],[2.704175827,49.373723441],[2.704157915,49.374446088],[2.704127042,49.37502848]],[[2.704119365,49.375166887],[2.70408568,49.375759159],[2.703941665,49.37710531],[2.70390435,49.377370385]],[[2.704127042,49.37502848],[2.704124118,49.375058136],[2.704122628,49.375077907],[2.704119365,49.375166887]],[[2.703931583,49.375025305],[2.703972588,49.373877549],[2.703977585,49.373726551],[2.703973356,49.373024523],[2.703911925,49.368140794]]]}',
            $geometry,
        );

        // When excluding highways, the result contains only Rue de Vaudherlant and the "bretelle".
        $geometry = $this->roadGeocoder->findSectionsInArea($area, excludeTypes: [$this->roadGeocoder::HIGHWAY]);
        $this->assertSame(
            '{"type":"MultiLineString","coordinates":[[[2.704047618,49.379991827],[2.704002207,49.37998812],[2.703927917,49.37997895],[2.703874323,49.37996174],[2.703838642,49.379941878],[2.703823638,49.379917572],[2.703819668,49.379889697],[2.703829635,49.379832194],[2.703868123,49.379603078],[2.703908085,49.379356887],[2.704045651,49.378176115],[2.704074021,49.377790571],[2.704148908,49.377215479],[2.704347775,49.376145415],[2.70449489,49.375697239],[2.704644914,49.375221205],[2.704661989,49.375124169],[2.704663706,49.375064848]],[[2.702812091,49.375080986],[2.703003456,49.37507786],[2.703226487,49.375073913],[2.703234746,49.375073934],[2.703444002,49.37507175],[2.703720699,49.37507153],[2.703928546,49.375074735],[2.704122628,49.375077907],[2.70422448,49.375080853],[2.704291927,49.375081917],[2.704411689,49.375082209],[2.704450238,49.375081405],[2.704547996,49.375078048],[2.704663706,49.375064848]],[[2.704663706,49.375064848],[2.705508168,49.374958144],[2.706102966,49.374938917],[2.706678394,49.374936719],[2.707757531,49.37495641]]]}',
            $geometry,
        );
    }

    public function testConvertPolygonToRoadLines(): void
    {
        $polygonCoordinates = [[[3.0739553997, 50.6424619948], [3.0739665207, 50.6424529935], [3.074522087, 50.6418609607], [3.0777787224, 50.63850051], [3.0776585827, 50.6384534293], [3.0744016773, 50.6418141505], [3.0744011534, 50.6418146998], [3.0738499776, 50.6424020525], [3.0733516646, 50.6427136294], [3.0734506159, 50.6427776181], [3.0739553997, 50.6424619948]]];
        $polygonGeometry = json_encode(['type' => 'Polygon', 'coordinates' => $polygonCoordinates]);
        $multiPolygonGeometry = json_encode(['type' => 'MultiPolygon', 'coordinates' => [$polygonCoordinates]]);

        $expectedLine = '{"type":"MultiLineString","coordinates":[[[3.073884143,50.64244362],[3.073451098,50.642714387]],[[3.073884143,50.64244362],[3.0739208,50.64241349]],[[3.0739208,50.64241349],[3.073925633,50.642408998]],[[3.077673753,50.638523301],[3.07447901,50.641819881]],[[3.07447901,50.641819881],[3.074444873,50.641855679]],[[3.074444873,50.641855679],[3.074444682,50.64185588]],[[3.074444682,50.64185588],[3.073925633,50.642408998]]]}';

        $this->assertSame($expectedLine, $this->roadGeocoder->convertPolygonRoadToLines($polygonGeometry));
        $this->assertSame($expectedLine, $this->roadGeocoder->convertPolygonRoadToLines($multiPolygonGeometry));
        $this->assertSame($expectedLine, $this->roadGeocoder->convertPolygonRoadToLines($expectedLine)); // Test not polygon
    }
}
