<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Application\Regulation\Query\Location\GetNamedStreetGeometryQuery;
use App\Application\Regulation\Query\Location\GetRawGeoJSONGeometryQuery;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\WholeCityException;
use PHPUnit\Framework\TestCase;

final class SaveWholeCityExceptionCommandTest extends TestCase
{
    public function testDefaultsToLaneButIncomplete(): void
    {
        $command = new SaveWholeCityExceptionCommand();

        // Défaut « Voie » pour afficher le sous-formulaire à l'ajout, mais sans données => incomplet.
        $this->assertSame(RoadTypeEnum::LANE->value, $command->roadType);
        $this->assertNull($command->namedStreet);
        $this->assertNull($command->rawGeoJSON);
        $this->assertFalse($command->isComplete());
        $this->assertNull($command->getActiveRoadCommand());
        $this->assertNull($command->getGeometryQuery());
        $this->assertNull($command->getExcludedRoadBanId());
    }

    public function testHydrateFromNamedStreetException(): void
    {
        $exception = new WholeCityException(
            uuid: 'uuid',
            location: $this->createMock(\App\Domain\Regulation\Location\Location::class),
            roadType: RoadTypeEnum::LANE->value,
            label: 'Rue de Paris',
            geometry: '<geom>',
            data: [
                'cityCode' => '59350',
                'roadBanId' => '59350_1234',
                'roadName' => 'Rue de Paris',
                'fromHouseNumber' => '10',
                'toHouseNumber' => '20',
                'direction' => DirectionEnum::BOTH->value,
            ],
        );

        $command = new SaveWholeCityExceptionCommand($exception);

        $this->assertSame(RoadTypeEnum::LANE->value, $command->roadType);
        $this->assertNotNull($command->namedStreet);
        $this->assertSame('59350', $command->namedStreet->cityCode);
        $this->assertSame('59350_1234', $command->namedStreet->roadBanId);
        $this->assertSame('Rue de Paris', $command->namedStreet->roadName);
        $this->assertSame('10', $command->namedStreet->fromHouseNumber);
        $this->assertNull($command->rawGeoJSON);
        $this->assertTrue($command->isComplete());
        $this->assertSame('Rue de Paris', $command->getLabel());
        $this->assertInstanceOf(GetNamedStreetGeometryQuery::class, $command->getGeometryQuery());
    }

    public function testHydrateFromRawGeoJSONException(): void
    {
        $exception = new WholeCityException(
            uuid: 'uuid',
            location: $this->createMock(\App\Domain\Regulation\Location\Location::class),
            roadType: RoadTypeEnum::RAW_GEOJSON->value,
            label: 'Zone piétonne',
            geometry: '<geom>',
            data: ['label' => 'Zone piétonne'],
        );

        $command = new SaveWholeCityExceptionCommand($exception);

        $this->assertSame(RoadTypeEnum::RAW_GEOJSON->value, $command->roadType);
        $this->assertNull($command->namedStreet);
        $this->assertNotNull($command->rawGeoJSON);
        $this->assertSame('Zone piétonne', $command->rawGeoJSON->label);
        $this->assertSame('<geom>', $command->rawGeoJSON->geometry);
        $this->assertTrue($command->isComplete());
        $this->assertSame('Zone piétonne', $command->getLabel());
        $this->assertInstanceOf(GetRawGeoJSONGeometryQuery::class, $command->getGeometryQuery());
    }

    public function testCleanDropsInactiveSubCommandForLane(): void
    {
        $command = new SaveWholeCityExceptionCommand();
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->namedStreet = new SaveNamedStreetCommand();
        $command->namedStreet->roadBanId = '59350_1234';
        $command->rawGeoJSON = new SaveRawGeoJSONCommand();
        $command->rawGeoJSON->label = 'leftover';

        $command->clean();

        $this->assertNotNull($command->namedStreet);
        $this->assertNull($command->rawGeoJSON);
        $this->assertSame($command->namedStreet, $command->getActiveRoadCommand());
    }

    public function testCleanDropsInactiveSubCommandForRawGeoJSON(): void
    {
        $command = new SaveWholeCityExceptionCommand();
        $command->roadType = RoadTypeEnum::RAW_GEOJSON->value;
        $command->namedStreet = new SaveNamedStreetCommand();
        $command->namedStreet->roadBanId = 'leftover';
        $command->rawGeoJSON = new SaveRawGeoJSONCommand();
        $command->rawGeoJSON->geometry = '<geom>';

        $command->clean();

        $this->assertNull($command->namedStreet);
        $this->assertNotNull($command->rawGeoJSON);
    }

    public function testEntireVoieIsExcludedByBanId(): void
    {
        $command = new SaveWholeCityExceptionCommand();
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->namedStreet = new SaveNamedStreetCommand();
        $command->namedStreet->roadBanId = '59350_1234';

        $this->assertSame('59350_1234', $command->getExcludedRoadBanId());
    }

    public function testSectionIsNotExcludedByBanId(): void
    {
        $command = new SaveWholeCityExceptionCommand();
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->namedStreet = new SaveNamedStreetCommand();
        $command->namedStreet->roadBanId = '59350_1234';
        $command->namedStreet->fromHouseNumber = '10';

        $this->assertNull($command->getExcludedRoadBanId());
    }

    public function testRawGeoJSONIsNotExcludedByBanId(): void
    {
        $command = new SaveWholeCityExceptionCommand();
        $command->roadType = RoadTypeEnum::RAW_GEOJSON->value;
        $command->rawGeoJSON = new SaveRawGeoJSONCommand();
        $command->rawGeoJSON->geometry = '<geom>';

        $this->assertNull($command->getExcludedRoadBanId());
    }

    public function testToDataForNamedStreet(): void
    {
        $command = new SaveWholeCityExceptionCommand();
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->namedStreet = new SaveNamedStreetCommand();
        $command->namedStreet->cityCode = '59350';
        $command->namedStreet->roadBanId = '59350_1234';
        $command->namedStreet->roadName = 'Rue de Paris';
        $command->namedStreet->direction = DirectionEnum::BOTH->value;

        $data = $command->toData();

        $this->assertSame('59350', $data['cityCode']);
        $this->assertSame('59350_1234', $data['roadBanId']);
        $this->assertSame('Rue de Paris', $data['roadName']);
        $this->assertArrayHasKey('direction', $data);
    }

    public function testSectionRoundTripPreservesPointTypes(): void
    {
        // Une exception "section" entre deux intersections doit se ré-éditer entièrement.
        $original = new SaveWholeCityExceptionCommand();
        $original->roadType = RoadTypeEnum::LANE->value;
        $original->namedStreet = new SaveNamedStreetCommand();
        $original->namedStreet->cityCode = '59350';
        $original->namedStreet->roadBanId = '59350_1234';
        $original->namedStreet->roadName = 'Rue de Paris';
        $original->namedStreet->fromPointType = 'intersection';
        $original->namedStreet->fromRoadBanId = '59350_1111';
        $original->namedStreet->fromRoadName = 'Rue A';
        $original->namedStreet->toPointType = 'intersection';
        $original->namedStreet->toRoadBanId = '59350_2222';
        $original->namedStreet->toRoadName = 'Rue B';
        $original->namedStreet->direction = DirectionEnum::BOTH->value;

        $persisted = new WholeCityException(
            uuid: 'uuid',
            location: $this->createMock(\App\Domain\Regulation\Location\Location::class),
            roadType: RoadTypeEnum::LANE->value,
            label: $original->getLabel(),
            geometry: '<geom>',
            data: $original->toData(),
        );

        $reloaded = new SaveWholeCityExceptionCommand($persisted);

        $this->assertSame('intersection', $reloaded->namedStreet->fromPointType);
        $this->assertSame('59350_1111', $reloaded->namedStreet->fromRoadBanId);
        $this->assertSame('Rue A', $reloaded->namedStreet->fromRoadName);
        $this->assertSame('intersection', $reloaded->namedStreet->toPointType);
        $this->assertSame('Rue B', $reloaded->namedStreet->toRoadName);
        // section => pas une voie entière, donc pas d'exclusion par BAN id
        $this->assertFalse($reloaded->namedStreet->getIsEntireStreet());
        $this->assertNull($reloaded->getExcludedRoadBanId());
    }

    public function testToDataForRawGeoJSON(): void
    {
        $command = new SaveWholeCityExceptionCommand();
        $command->roadType = RoadTypeEnum::RAW_GEOJSON->value;
        $command->rawGeoJSON = new SaveRawGeoJSONCommand();
        $command->rawGeoJSON->label = 'Zone piétonne';

        $this->assertSame(['label' => 'Zone piétonne'], $command->toData());
    }
}
