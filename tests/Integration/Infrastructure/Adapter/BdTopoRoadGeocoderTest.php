<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Adapter;

use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\GeocodingFailureException;
use App\Domain\Geography\Coordinates;
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

    private function provideTestComputeRoadLine(): array
    {
        $rueDeLEgliseGeometry = '{"type":"MultiLineString","coordinates":[[[7.358808082,48.077073777],[7.3588364,48.077042087],[7.358875,48.077004608],[7.359079081,48.076853753],[7.359232947,48.076729993],[7.359335101,48.076647802],[7.359432411,48.076572099],[7.359505142,48.076491898]]]}';

        return [
            'standard' => [
                'roadName' => 'Rue de Famars',
                'inseeCode' => '59606', // Valenciennes
                'geometry' => '{"type":"MultiLineString","coordinates":[[[3.521789594,50.354739626],[3.521764707,50.354775643],[3.521598673,50.355004387],[3.521507319,50.355126875]],[[3.521464982,50.355375745],[3.521433481,50.355314828]],[[3.522862085,50.352877527],[3.522829301,50.352963855],[3.522741262,50.353134812]],[[3.523000274,50.352264637],[3.522940617,50.352465999],[3.522934214,50.352525281],[3.522935218,50.35262224]],[[3.52235843,50.356688146],[3.522243056,50.356523439],[3.522150571,50.356400831],[3.522082435,50.356325704],[3.521979012,50.356230975],[3.521923783,50.356182727],[3.521851614,50.356124675]],[[3.522631994,50.357222078],[3.522606363,50.357186275],[3.522603315,50.357162945],[3.522634011,50.357145757],[3.522629615,50.357127819],[3.522454698,50.356905007]],[[3.522454698,50.356905007],[3.522376207,50.356778749],[3.522382825,50.356740116],[3.52235843,50.356688146]],[[3.521507319,50.355126875],[3.521433481,50.355314828]],[[3.522935218,50.35262224],[3.522936816,50.352641087],[3.522922171,50.352717463],[3.522862085,50.352877527]],[[3.522232905,50.354048237],[3.52215435,50.354186831],[3.522034181,50.354372287]],[[3.521851614,50.356124675],[3.521786581,50.356078264],[3.521725582,50.356014778],[3.521658859,50.355940542],[3.521614653,50.355872496],[3.521568534,50.355755079],[3.521531599,50.355575674],[3.52150244,50.355469857],[3.521464982,50.355375745]],[[3.522741262,50.353134812],[3.522596769,50.353410152],[3.52235284,50.353840336]],[[3.52235284,50.353840336],[3.522289434,50.353951035],[3.522232905,50.354048237]],[[3.522034181,50.354372287],[3.521887767,50.354598256],[3.521789594,50.354739626]]]}',
            ],
            'normalize_hyphen' => [
                'roadName' => 'rue sainte-catherine',
                'inseeCode' => '59606', // Valenciennes
                'geometry' => '{"type":"MultiLineString","coordinates":[[[3.520653678,50.355629658],[3.521166979,50.355463193],[3.521282977,50.355416915],[3.521342983,50.355384341],[3.521389022,50.35535811],[3.521433481,50.355314828]],[[3.520578142,50.355653319],[3.520653678,50.355629658]],[[3.520578142,50.355653319],[3.520421708,50.355724905],[3.520177422,50.35585073],[3.519908236,50.356011673],[3.519837215,50.356064943]]]}',
            ],
            'normalize_quote_accent_case' => [
                'roadName' => 'RUE de l\'éGlIsE',
                'inseeCode' => '68066', // Colmar
                'geometry' => $rueDeLEgliseGeometry,
            ],
            'normalize_accent_special' => [
                'roadName' => 'rue de l’eglise',
                'inseeCode' => '68066', // Colmar
                'geometry' => $rueDeLEgliseGeometry,
            ],
        ];
    }

    /**
     * @dataProvider provideTestComputeRoadLine
     */
    public function testComputeRoadLine(string $roadName, string $inseeCode, string $geometry): void
    {
        $this->assertSame($geometry, $this->roadGeocoder->computeRoadLine($roadName, $inseeCode));
    }

    public function testComputeRoadLineErrorNotFound(): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessageMatches('/no result found/');

        $this->roadGeocoder->computeRoadLine('Does not exist', '59606');
    }

    private function provideTestFindRoads(): array
    {
        return [
            'empty-administratorDoesNotExist' => ['D90', 'Administrator Does Not Exist', []],
            'empty-searchNoResults' => ['does not exist', 'Ardèche', []],
            'empty-searchNotD' => ['90', 'Ardèche', []],
            'success' => [
                'D90',
                'Ardèche',
                [
                    ['roadNumber' => 'D901'],
                    ['roadNumber' => 'D902'],
                    ['roadNumber' => 'D906'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideTestFindRoads
     */
    public function testFindRoads(string $search, string $administrator, array $roadNumbers): void
    {
        $this->assertEquals($roadNumbers, $this->roadGeocoder->findRoads($search, $administrator));
    }

    private function provideTestComputeRoad(): array
    {
        // Departmental road lines are very long, use hashes
        return [
            'success' => [
                'D906',
                'Ardèche',
                '188aafa16d56b14ac98387d369be5798',
            ],
            'multiple-results' => [
                'D60',
                'Aisne',
                'a443edf6c51aa195a0e7b9c703fce783',
            ],
        ];
    }

    /**
     * @dataProvider provideTestComputeRoad
     */
    public function testComputeRoad(string $roadNumber, string $administrator, string $geometryMd5): void
    {
        $this->assertSame($geometryMd5, md5($this->roadGeocoder->computeRoad($roadNumber, $administrator)));
    }

    private function provideTestComputeRoadNoResult(): array
    {
        return [
            'noResult-roadDoesNotExist' => ['D9000', 'Ardèche'],
            'noResult-administratorDoesNotExist' => ['D902', 'La Dèche'],
        ];
    }

    /**
     * @dataProvider provideTestComputeRoadNoResult
     */
    public function testComputeRoadNoResult(string $roadNumber, string $administrator): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessageMatches('/no result found/');

        $this->roadGeocoder->computeRoad($roadNumber, $administrator);
    }

    private function provideTestComputeReferencePoint(): array
    {
        return [
            'success' => [
                'D906',
                'Ardèche',
                '34',
                'U',
                0,
                Coordinates::fromLonLat(3.905822232, 44.592400215),
            ],
            'abscissa' => [
                'D906',
                'Ardèche',
                '34',
                'U',
                500,
                Coordinates::fromLonLat(3.905982782, 44.596555025),
            ],
        ];
    }

    /**
     * @dataProvider provideTestComputeReferencePoint
     */
    public function testComputeReferencePoint(string $roadNumber, string $administrator, string $pointNumber, string $side, int $abscissa, Coordinates $coords): void
    {
        $lineGeometry = $this->roadGeocoder->computeRoad($roadNumber, $administrator);

        $this->assertEquals($coords, $this->roadGeocoder->computeReferencePoint($lineGeometry, $administrator, $roadNumber, $pointNumber, $side, $abscissa));
    }

    public function testComputeReferencePointErrorNoResult(): void
    {
        $this->expectException(GeocodingFailureException::class);
        $this->expectExceptionMessageMatches('/no result found/');

        $lineGeometry = $this->roadGeocoder->computeRoad(roadNumber: 'D906', administrator: 'Ardèche');

        $this->roadGeocoder->computeReferencePoint(
            $lineGeometry,
            administrator: 'Ardèche',
            roadNumber: 'D906',
            pointNumber: '1',
            side: 'U',
            abscissa: 0,
        );
    }

    public function testComputeReferencePointErrorOutOfRange(): void
    {
        $this->expectException(AbscissaOutOfRangeException::class);

        $lineGeometry = $this->roadGeocoder->computeRoad(roadNumber: 'D906', administrator: 'Ardèche');

        $this->roadGeocoder->computeReferencePoint(
            $lineGeometry,
            administrator: 'Ardèche',
            roadNumber: 'D906',
            pointNumber: '34',
            side: 'U',
            abscissa: 4000,
        );
    }

    private function provideTestFindRoadNames(): array
    {
        return [
            'no-result' => ['does not exist', '59606', []],
            'success' => ['famars', '59606', ['Rue De Famars']],
            'compound-word' => ['rue sainte-catherine', '59606', [
                'Rue Sainte-Catherine',
                'Rue Du Faubourg Sainte-Catherine',
                'Lotissement Sainte-Catherine',
                'Rue Sainte-Barbe',
                'Rue Catherine Samie',
                'Résidence Catherine',
            ]],
            'quote_accent' => ['l\'église', '68066', [
                'Rue De L\'Église',
            ]],
        ];
    }

    /**
     * @dataProvider provideTestFindRoadNames
     */
    public function testFindRoadNames(string $search, string $cityCode, array $roadNames): void
    {
        $this->assertEquals($roadNames, $this->roadGeocoder->findRoadNames($search, $cityCode));
    }

    private function provideTestFindIntersectingRoadNames(): array
    {
        return [
            'no-result' => ['', 'does not exist', '59606', []],
            'no-result-roadName-not-exact' => ['', 'rue saints victor', '59368', []],
            'success' => ['', 'rue saint victor', '59368', ['Rue Des Gantois', 'Rue Georges Pompidou']],
            'success-case-insensitive' => ['', 'rue SAINT VICtor', '59368', ['Rue Des Gantois', 'Rue Georges Pompidou']],
            'success-search' => ['gant', 'rue saint victor', '59368', ['Rue Des Gantois']],
        ];
    }

    /**
     * @dataProvider provideTestFindIntersectingRoadNames
     */
    public function testFindIntersectingRoads(string $search, string $roadName, string $cityCode, array $roadNames): void
    {
        $this->assertEquals($roadNames, $this->roadGeocoder->findIntersectingRoadNames($search, $roadName, $cityCode));
    }

    private function provideTestComputeIntersection(): array
    {
        return [
            'success' => ['rue saint victor', 'rue des gantois', '59368', Coordinates::fromLonLat(3.0677371147030987, 50.653028447505825)],
        ];
    }

    /**
     * @dataProvider provideTestComputeIntersection
     */
    public function testComputeIntersection(string $roadName, string $otherRoadName, string $cityCode, Coordinates $coords): void
    {
        $this->assertEquals($coords, $this->roadGeocoder->computeIntersection($roadName, $otherRoadName, $cityCode));
    }

    private function provideTestComputeIntersectionError(): array
    {
        return [
            'roadName-does-not-exist' => ['does not exist', 'rue des gantois', '59368'],
            'otherRoadName-does-not-exist' => ['rue saint victor', 'does not exist', '59368'],
            'roads-do-not-intersect' => ['rue saint victor', 'rue de flandre', '59368'],
        ];
    }

    /**
     * @dataProvider provideTestComputeIntersectionError
     */
    public function testComputeIntersectionError(string $roadName, string $otherRoadName, string $cityCode): void
    {
        $this->expectException(GeocodingFailureException::class);

        $this->roadGeocoder->computeIntersection($roadName, $otherRoadName, $cityCode);
    }
}
