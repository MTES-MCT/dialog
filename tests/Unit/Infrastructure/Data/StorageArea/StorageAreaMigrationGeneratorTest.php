<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Data\StorageArea;

use App\Application\Exception\GeocodingFailureException;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadSectionMakerInterface;
use App\Infrastructure\Data\StorageArea\StorageAreaMigrationGenerator;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

final class StorageAreaMigrationGeneratorTest extends TestCase
{
    private $bdtopo2025Connection;
    private $roadGeocoder;
    private $roadSectionMaker;
    private $testRows;
    private $generator;

    protected function setUp(): void
    {
        $this->bdtopo2025Connection = $this->createMock(Connection::class);
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->roadSectionMaker = $this->createMock(RoadSectionMakerInterface::class);
        $this->generator = new StorageAreaMigrationGenerator($this->bdtopo2025Connection, $this->roadGeocoder, $this->roadSectionMaker);
        $decoder = new CsvEncoder();
        $this->testRows = $decoder->decode(file_get_contents(__DIR__ . '/../../../../fixtures/aires_de_stockage_test.csv'), 'csv');
    }

    public function testGenerateEmpty(): void
    {
        $this->assertSame('', $this->generator->makeMigrationSql([]));
    }

    public function testGenerateGeocodingExceptionIgnored(): void
    {
        $rows = [$this->testRows[0]];

        $this->bdtopo2025Connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(['gestionnaire' => 'DIR Centre-Est']);
        $matcher = self::once();

        $this->roadGeocoder
            ->expects($matcher)
            ->method('computeRoad')
            ->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertEquals('Nationale', $parameters[0]);
                    $this->assertEquals('DIR Centre-Est', $parameters[1]);
                    $this->assertEquals('N79', $parameters[2]);
                }

                return 'roadGeometry1';
            });

        $this->roadSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->willThrowException(new GeocodingFailureException());

        $this->assertSame('', $this->generator->makeMigrationSql($rows));
    }

    public function testGenerate(): void
    {
        $this->bdtopo2025Connection
            ->expects(self::exactly(3))
            ->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls(
                ['gestionnaire' => 'DIR Centre-Est'],
                ['gestionnaire' => 'DIR Atlantique'],
                ['gestionnaire' => 'DIR Atlantique'],
            );
        $matcher = self::exactly(3);

        $this->roadGeocoder
            ->expects($matcher)
            ->method('computeRoad')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertEquals('Nationale', $parameters[0]);
                    $this->assertEquals('DIR Centre-Est', $parameters[1]);
                    $this->assertEquals('N79', $parameters[2]);

                    return 'roadGeometry1';
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertEquals('Nationale', $parameters[0]);
                    $this->assertEquals('DIR Atlantique', $parameters[1]);
                    $this->assertEquals('N10', $parameters[2]);

                    return 'roadGeometry2';
                }
                if ($matcher->numberOfInvocations() === 3) {
                    $this->assertEquals('Nationale', $parameters[0]);
                    $this->assertEquals('DIR Atlantique', $parameters[1]);
                    $this->assertEquals('N10', $parameters[2]);

                    return 'roadGeometry3';
                }
            });
        $matcher = self::exactly(3);

        $this->roadSectionMaker
            ->expects($matcher)
            ->method('computeSection')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertEquals('roadGeometry1', $parameters[0]);
                    $this->assertEquals('Nationale', $parameters[1]);
                    $this->assertEquals('DIR Centre-Est', $parameters[2]);
                    $this->assertEquals('N79', $parameters[3]);
                    $this->assertEquals('71', $parameters[4]);
                    $this->assertEquals('50', $parameters[5]);
                    $this->assertEquals('D', $parameters[6]);
                    $this->assertEquals(600, $parameters[7]);
                    $this->assertEquals('71', $parameters[8]);
                    $this->assertEquals('51', $parameters[9]);
                    $this->assertEquals('D', $parameters[10]);
                    $this->assertEquals(150, $parameters[11]);

                    return 'geom1';
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertEquals('roadGeometry2', $parameters[0]);
                    $this->assertEquals('Nationale', $parameters[1]);
                    $this->assertEquals('DIR Atlantique', $parameters[2]);
                    $this->assertEquals('N10', $parameters[3]);
                    $this->assertEquals('86', $parameters[4]);
                    $this->assertEquals('92', $parameters[5]);
                    $this->assertEquals('G', $parameters[6]);
                    $this->assertEquals(0, $parameters[7]);
                    $this->assertEquals('86', $parameters[8]);
                    $this->assertEquals('87', $parameters[9]);
                    $this->assertEquals('G', $parameters[10]);
                    $this->assertEquals(800, $parameters[11]);

                    return 'geom2';
                }
                if ($matcher->numberOfInvocations() === 3) {
                    $this->assertEquals('roadGeometry3', $parameters[0]);
                    $this->assertEquals('Nationale', $parameters[1]);
                    $this->assertEquals('DIR Atlantique', $parameters[2]);
                    $this->assertEquals('N10', $parameters[3]);
                    $this->assertEquals('16', $parameters[4]);
                    $this->assertEquals('29', $parameters[5]);
                    $this->assertEquals('D', $parameters[6]);
                    $this->assertEquals(200, $parameters[7]);
                    $this->assertEquals('16', $parameters[8]);
                    $this->assertEquals('29', $parameters[9]);
                    $this->assertEquals('D', $parameters[10]);
                    $this->assertEquals(300, $parameters[11]);

                    return 'geom3';
                }
            });

        $sql = $this->generator->makeMigrationSql($this->testRows);

        $expectedSql = trim(
            'INSERT INTO storage_area (uuid, source_id, description, administrator, road_number, from_department_code, from_point_number, from_side, from_abscissa, to_department_code, to_point_number, to_side, to_abscissa, geometry) VALUES
(uuid_generate_v4(), \'Pl_5\', \'La Chapelle du Mont de France _ 42-71-N79-51-1 _ capacité 30 - située sur BAU  _ District Mâcon _ CEI CHARANY LES MACON\', \'DIR Centre-Est\', \'N79\', \'71\', \'50\', \'D\', 600, \'71\', \'51\', \'D\', 150, ST_GeomFromGeoJSON(\'geom1\')),
(uuid_generate_v4(), \'PL_86_N0010_ ??_N0010\', \'Stockage 420 PL pleine voie –  sens Bordeaux/Poitiers\', \'DIR Atlantique\', \'N10\', \'86\', \'92\', \'G\', 0, \'86\', \'87\', \'G\', 800, ST_GeomFromGeoJSON(\'geom2\')),
(uuid_generate_v4(), \'PL_16_N0010_04_N0010\', \'Stockage 400 PL hors axe – Aire de repos Maine de Boixe ouest + restaurant La Belle Cantinière – accès sens nord/sud\', \'DIR Atlantique\', \'N10\', \'16\', \'29\', \'D\', 200, \'16\', \'29\', \'D\', 300, ST_GeomFromGeoJSON(\'geom3\'))
ON CONFLICT (source_id) DO UPDATE
SET description = EXCLUDED.description, administrator = EXCLUDED.administrator, road_number = EXCLUDED.road_number, from_department_code = EXCLUDED.from_department_code, from_point_number = EXCLUDED.from_point_number, from_side = EXCLUDED.from_side, from_abscissa = EXCLUDED.from_abscissa, to_department_code = EXCLUDED.to_department_code, to_point_number = EXCLUDED.to_point_number, to_side = EXCLUDED.to_side, to_abscissa = EXCLUDED.to_abscissa, geometry = EXCLUDED.geometry;
',
        );

        $this->assertSame($expectedSql, $sql);
    }
}
