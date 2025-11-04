<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Mapper\Transformers;

use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Infrastructure\DTO\Event\SaveLocationDTO;
use App\Infrastructure\DTO\Event\SaveNamedStreetDTO;
use App\Infrastructure\DTO\Event\SaveNumberedRoadDTO;
use App\Infrastructure\DTO\Event\SaveRawGeoJSONDTO;
use App\Infrastructure\Mapper\Transformers\LocationsTransformer;
use PHPUnit\Framework\TestCase;

final class LocationsTransformerTest extends TestCase
{
    public function testTransformsNamedStreet(): void
    {
        $dto = new SaveLocationDTO();
        $dto->roadType = RoadTypeEnum::LANE;
        $dto->namedStreet = new SaveNamedStreetDTO();
        $dto->namedStreet->cityCode = '75056';
        $dto->namedStreet->cityLabel = 'Paris';
        $dto->namedStreet->roadName = 'Rue Exemple';
        $dto->namedStreet->fromPointType = 'houseNumber';
        $dto->namedStreet->fromHouseNumber = '10';
        $dto->namedStreet->fromRoadName = 'Rue A';
        $dto->namedStreet->toPointType = 'houseNumber';
        $dto->namedStreet->toHouseNumber = '20';
        $dto->namedStreet->toRoadName = 'Rue B';
        $dto->namedStreet->geometry = '{"type":"LineString"}';
        $dto->namedStreet->direction = DirectionEnum::BOTH;

        $commands = LocationsTransformer::toCommands([$dto]);

        self::assertCount(1, $commands);
        $cmd = $commands[0];
        self::assertSame(RoadTypeEnum::LANE->value, $cmd->roadType);
        self::assertNotNull($cmd->namedStreet);
        self::assertSame('75056', $cmd->namedStreet->cityCode);
        self::assertSame('Paris', $cmd->namedStreet->cityLabel);
        self::assertSame('Rue Exemple', $cmd->namedStreet->roadName);
        self::assertSame('houseNumber', $cmd->namedStreet->fromPointType);
        self::assertSame('10', $cmd->namedStreet->fromHouseNumber);
        self::assertSame('Rue A', $cmd->namedStreet->fromRoadName);
        self::assertSame('houseNumber', $cmd->namedStreet->toPointType);
        self::assertSame('20', $cmd->namedStreet->toHouseNumber);
        self::assertSame('Rue B', $cmd->namedStreet->toRoadName);
        self::assertSame('{"type":"LineString"}', $cmd->namedStreet->geometry);
        self::assertSame(DirectionEnum::BOTH->value, $cmd->namedStreet->direction);
    }

    public function testTransformsDepartmentalRoad(): void
    {
        $dto = new SaveLocationDTO();
        $dto->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD;
        $dto->departmentalRoad = new SaveNumberedRoadDTO();
        $dto->departmentalRoad->administrator = 'CD92';
        $dto->departmentalRoad->roadNumber = 'D7';
        $dto->departmentalRoad->fromDepartmentCode = '92';
        $dto->departmentalRoad->fromPointNumber = '1';
        $dto->departmentalRoad->fromAbscissa = 100;
        $dto->departmentalRoad->fromSide = 'LEFT';
        $dto->departmentalRoad->toDepartmentCode = '92';
        $dto->departmentalRoad->toPointNumber = '2';
        $dto->departmentalRoad->toAbscissa = 200;
        $dto->departmentalRoad->toSide = 'RIGHT';
        $dto->departmentalRoad->direction = DirectionEnum::A_TO_B;
        $dto->departmentalRoad->geometry = '{"type":"LineString"}';

        $commands = LocationsTransformer::toCommands([$dto]);

        self::assertCount(1, $commands);
        $cmd = $commands[0];
        self::assertNotNull($cmd->departmentalRoad);
        self::assertSame('CD92', $cmd->departmentalRoad->administrator);
        self::assertSame('D7', $cmd->departmentalRoad->roadNumber);
        self::assertSame('92', $cmd->departmentalRoad->fromDepartmentCode);
        self::assertSame('1', $cmd->departmentalRoad->fromPointNumber);
        self::assertSame(100, $cmd->departmentalRoad->fromAbscissa);
        self::assertSame('LEFT', $cmd->departmentalRoad->fromSide);
        self::assertSame('92', $cmd->departmentalRoad->toDepartmentCode);
        self::assertSame('2', $cmd->departmentalRoad->toPointNumber);
        self::assertSame(200, $cmd->departmentalRoad->toAbscissa);
        self::assertSame('RIGHT', $cmd->departmentalRoad->toSide);
        self::assertSame(DirectionEnum::A_TO_B->value, $cmd->departmentalRoad->direction);
        self::assertSame('{"type":"LineString"}', $cmd->departmentalRoad->geometry);
    }

    public function testTransformsNationalRoad(): void
    {
        $dto = new SaveLocationDTO();
        $dto->roadType = RoadTypeEnum::NATIONAL_ROAD;
        $dto->nationalRoad = new SaveNumberedRoadDTO();
        $dto->nationalRoad->administrator = 'DIRIF';
        $dto->nationalRoad->roadNumber = 'N118';
        $dto->nationalRoad->fromDepartmentCode = '92';
        $dto->nationalRoad->fromPointNumber = '5';
        $dto->nationalRoad->fromAbscissa = 10;
        $dto->nationalRoad->fromSide = 'RIGHT';
        $dto->nationalRoad->toDepartmentCode = '78';
        $dto->nationalRoad->toPointNumber = '12';
        $dto->nationalRoad->toAbscissa = 20;
        $dto->nationalRoad->toSide = 'LEFT';
        $dto->nationalRoad->direction = DirectionEnum::B_TO_A;
        $dto->nationalRoad->geometry = '{"type":"LineString"}';

        $commands = LocationsTransformer::toCommands([$dto]);

        self::assertCount(1, $commands);
        $cmd = $commands[0];
        self::assertNotNull($cmd->nationalRoad);
        self::assertSame('DIRIF', $cmd->nationalRoad->administrator);
        self::assertSame('N118', $cmd->nationalRoad->roadNumber);
        self::assertSame('92', $cmd->nationalRoad->fromDepartmentCode);
        self::assertSame('5', $cmd->nationalRoad->fromPointNumber);
        self::assertSame(10, $cmd->nationalRoad->fromAbscissa);
        self::assertSame('RIGHT', $cmd->nationalRoad->fromSide);
        self::assertSame('78', $cmd->nationalRoad->toDepartmentCode);
        self::assertSame('12', $cmd->nationalRoad->toPointNumber);
        self::assertSame(20, $cmd->nationalRoad->toAbscissa);
        self::assertSame('LEFT', $cmd->nationalRoad->toSide);
        self::assertSame(DirectionEnum::B_TO_A->value, $cmd->nationalRoad->direction);
        self::assertSame('{"type":"LineString"}', $cmd->nationalRoad->geometry);
    }

    public function testTransformsRawGeoJSON(): void
    {
        $dto = new SaveLocationDTO();
        $dto->roadType = RoadTypeEnum::RAW_GEOJSON;
        $dto->rawGeoJSON = new SaveRawGeoJSONDTO();
        $dto->rawGeoJSON->label = 'Zone travaux';
        $dto->rawGeoJSON->geometry = '{"type":"Polygon"}';

        $commands = LocationsTransformer::toCommands([$dto]);

        self::assertCount(1, $commands);
        $cmd = $commands[0];
        self::assertNotNull($cmd->rawGeoJSON);
        self::assertSame('Zone travaux', $cmd->rawGeoJSON->label);
        self::assertSame('{"type":"Polygon"}', $cmd->rawGeoJSON->geometry);
    }
}
