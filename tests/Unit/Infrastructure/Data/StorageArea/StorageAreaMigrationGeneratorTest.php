<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Data\StorageArea;

use App\Application\Exception\GeocodingFailureException;
use App\Application\RoadGeocoderInterface;
use App\Application\RoadSectionMakerInterface;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Infrastructure\Adapter\CsvParser;
use App\Infrastructure\Data\StorageArea\StorageAreaMigrationGenerator;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class StorageAreaMigrationGeneratorTest extends TestCase
{
    private $bdtopoConnection;
    private $roadGeocoder;
    private $roadSectionMaker;
    private $generator;
    private $csvParser;

    protected function setUp(): void
    {
        $this->bdtopoConnection = $this->createMock(Connection::class);
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->roadSectionMaker = $this->createMock(RoadSectionMakerInterface::class);
        $this->generator = new StorageAreaMigrationGenerator($this->bdtopoConnection, $this->roadGeocoder, $this->roadSectionMaker);
        $this->csvParser = new CsvParser();
    }

    public function testGenerateEmpty(): void
    {
        $this->assertSame('', $this->generator->makeMigrationSql([]));
    }

    public function testGenerateGeocodingExceptionIgnored(): void
    {
        $rows = $this->csvParser->parseAssociative(file_get_contents(__DIR__ . '/../../../../fixtures/aires_de_stockage_test.csv'));
        $rows = [$rows[0]];

        $this->bdtopoConnection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(['gestionnaire' => 'DIR Centre-Est']);

        $this->roadGeocoder
            ->expects(self::once())
            ->method('computeRoad')
            ->withConsecutive(['Nationale', 'DIR Centre-Est', 'N79'])
            ->willReturn('roadGeometry1');

        $this->roadSectionMaker
            ->expects(self::once())
            ->method('computeSection')
            ->willThrowException(new GeocodingFailureException());

        $this->assertSame('', $this->generator->makeMigrationSql($rows));
    }

    public function testGenerate(): void
    {
        $rows = $this->csvParser->parseAssociative(file_get_contents(__DIR__ . '/../../../../fixtures/aires_de_stockage_test.csv'));

        $this->bdtopoConnection
            ->expects(self::exactly(3))
            ->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls(
                ['gestionnaire' => 'DIR Centre-Est'],
                ['gestionnaire' => 'DIR Atlantique'],
                ['gestionnaire' => 'DIR Atlantique'],
            );

        $this->roadGeocoder
            ->expects(self::exactly(3))
            ->method('computeRoad')
            ->withConsecutive(
                ['Nationale', 'DIR Centre-Est', 'N79'],
                ['Nationale', 'DIR Atlantique', 'N10'],
                ['Nationale', 'DIR Atlantique', 'N10'],
            )
            ->willReturnOnConsecutiveCalls(
                'roadGeometry1',
                'roadGeometry2',
                'roadGeometry3',
            );

        $this->roadSectionMaker
            ->expects(self::exactly(3))
            ->method('computeSection')
            ->withConsecutive(
                [
                    'roadGeometry1',
                    'Nationale',
                    'DIR Centre-Est',
                    'N79',
                    '50',
                    'D',
                    600,
                    '51',
                    'D',
                    150,
                    DirectionEnum::BOTH->value,
                ],
                [
                    'roadGeometry2',
                    'Nationale',
                    'DIR Atlantique',
                    'N10',
                    '92',
                    'G',
                    0,
                    '87',
                    'G',
                    800,
                    DirectionEnum::BOTH->value,
                ],
                [
                    'roadGeometry3',
                    'Nationale',
                    'DIR Atlantique',
                    'N10',
                    '29',
                    'D',
                    200,
                    '29',
                    'D',
                    300,
                    DirectionEnum::BOTH->value,
                ],
            )
            ->willReturnOnConsecutiveCalls('geom1', 'geom2', 'geom3');

        $sql = $this->generator->makeMigrationSql($rows);

        $expectedSql = trim(
            'INSERT INTO storage_area (uuid, source_id, description, administrator, road_number, from_point_number, from_side, from_abscissa, to_point_number, to_side, to_abscissa, geometry) VALUES
(uuid_generate_v4(), \'Pl_5\', \'La Chapelle du Mont de France _ 42-71-N79-51-1 _ capacité 30 - située sur BAU  _ District Mâcon _ CEI CHARANY LES MACON\', \'DIR Centre-Est\', \'N79\', \'50\', \'D\', 600, \'51\', \'D\', 150, ST_GeomFromGeoJSON(\'geom1\')),
(uuid_generate_v4(), \'PL_86_N0010_ ??_N0010\', \'Stockage 420 PL pleine voie –  sens Bordeaux/Poitiers\', \'DIR Atlantique\', \'N10\', \'92\', \'G\', 0, \'87\', \'G\', 800, ST_GeomFromGeoJSON(\'geom2\')),
(uuid_generate_v4(), \'PL_16_N0010_04_N0010\', \'Stockage 400 PL hors axe – Aire de repos Maine de Boixe ouest + restaurant La Belle Cantinière – accès sens nord/sud\', \'DIR Atlantique\', \'N10\', \'29\', \'D\', 200, \'29\', \'D\', 300, ST_GeomFromGeoJSON(\'geom3\'))
ON CONFLICT (source_id) DO UPDATE
SET description = EXCLUDED.description, administrator = EXCLUDED.administrator, road_number = EXCLUDED.road_number, from_point_number = EXCLUDED.from_point_number, from_side = EXCLUDED.from_side, from_abscissa = EXCLUDED.from_abscissa, to_point_number = EXCLUDED.to_point_number, to_side = EXCLUDED.to_side, to_abscissa = EXCLUDED.to_abscissa, geometry = EXCLUDED.geometry;
',
        );

        $this->assertSame($expectedSql, $sql);
    }
}
