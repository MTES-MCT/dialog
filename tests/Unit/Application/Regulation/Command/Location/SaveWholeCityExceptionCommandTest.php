<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Application\Regulation\Command\Location\SaveZoneCommand;
use App\Application\Regulation\Query\Location\GetNamedStreetGeometryQuery;
use App\Application\Regulation\Query\Location\GetNumberedRoadGeometryQuery;
use App\Application\Regulation\Query\Location\GetRawGeoJSONGeometryQuery;
use App\Application\Regulation\Query\Location\GetZoneGeometryQuery;
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
        $this->assertNull($command->departmentalRoad);
        $this->assertNull($command->nationalRoad);
        $this->assertNull($command->zone);
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

    public function testHydrateFromDepartmentalRoadException(): void
    {
        $exception = new WholeCityException(
            uuid: 'uuid',
            location: $this->createMock(\App\Domain\Regulation\Location\Location::class),
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD->value,
            label: 'D110',
            geometry: '<geom>',
            data: [
                'administrator' => 'Ardèche',
                'roadNumber' => 'D110',
                'fromDepartmentCode' => '07',
                'fromPointNumber' => '6',
                'fromAbscissa' => 100,
                'fromSide' => 'D',
                'toDepartmentCode' => '07',
                'toPointNumber' => '15',
                'toAbscissa' => 650,
                'toSide' => 'D',
                'direction' => DirectionEnum::BOTH->value,
            ],
        );

        $command = new SaveWholeCityExceptionCommand($exception);

        $this->assertSame(RoadTypeEnum::DEPARTMENTAL_ROAD->value, $command->roadType);
        $this->assertNotNull($command->departmentalRoad);
        $this->assertNull($command->nationalRoad);
        $this->assertSame(RoadTypeEnum::DEPARTMENTAL_ROAD->value, $command->departmentalRoad->roadType);
        $this->assertSame('Ardèche', $command->departmentalRoad->administrator);
        $this->assertSame('D110', $command->departmentalRoad->roadNumber);
        $this->assertSame('6', $command->departmentalRoad->fromPointNumber);
        $this->assertSame(100, $command->departmentalRoad->fromAbscissa);
        // Les champs encodés du formulaire doivent être reconstruits pour la ré-édition.
        $this->assertSame('07##6', $command->departmentalRoad->fromPointNumberWithDepartmentCode);
        $this->assertSame('07##15', $command->departmentalRoad->toPointNumberWithDepartmentCode);
        $this->assertTrue($command->isComplete());
        $this->assertSame('D110', $command->getLabel());
        $this->assertNull($command->getExcludedRoadBanId());
        $this->assertInstanceOf(GetNumberedRoadGeometryQuery::class, $command->getGeometryQuery());
    }

    public function testHydrateFromNationalRoadException(): void
    {
        $exception = new WholeCityException(
            uuid: 'uuid',
            location: $this->createMock(\App\Domain\Regulation\Location\Location::class),
            roadType: RoadTypeEnum::NATIONAL_ROAD->value,
            label: 'N176',
            geometry: '<geom>',
            data: [
                'administrator' => 'DIR Ouest',
                'roadNumber' => 'N176',
                'fromDepartmentCode' => null,
                'fromPointNumber' => '1',
                'fromAbscissa' => 0,
                'fromSide' => 'D',
                'toDepartmentCode' => null,
                'toPointNumber' => '2',
                'toAbscissa' => 0,
                'toSide' => 'D',
                'direction' => DirectionEnum::BOTH->value,
            ],
        );

        $command = new SaveWholeCityExceptionCommand($exception);

        $this->assertSame(RoadTypeEnum::NATIONAL_ROAD->value, $command->roadType);
        $this->assertNull($command->departmentalRoad);
        $this->assertNotNull($command->nationalRoad);
        $this->assertSame('DIR Ouest', $command->nationalRoad->administrator);
        $this->assertSame('N176', $command->nationalRoad->roadNumber);
        $this->assertTrue($command->isComplete());
        $this->assertSame('N176', $command->getLabel());
        $this->assertSame($command->nationalRoad, $command->getActiveRoadCommand());
    }

    public function testHydrateFromZoneException(): void
    {
        $exception = new WholeCityException(
            uuid: 'uuid',
            location: $this->createMock(\App\Domain\Regulation\Location\Location::class),
            roadType: RoadTypeEnum::ZONE->value,
            label: 'Quartier des Halles',
            geometry: '<tronçons calculés>',
            data: [
                'label' => 'Quartier des Halles',
                'geometry' => '<polygone dessiné>',
            ],
        );

        $command = new SaveWholeCityExceptionCommand($exception);

        $this->assertSame(RoadTypeEnum::ZONE->value, $command->roadType);
        $this->assertNotNull($command->zone);
        $this->assertSame('Quartier des Halles', $command->zone->label);
        // La ré-édition doit repartir du polygone dessiné, pas des tronçons calculés.
        $this->assertSame('<polygone dessiné>', $command->zone->geometry);
        $this->assertTrue($command->isComplete());
        $this->assertSame('Quartier des Halles', $command->getLabel());
        $this->assertNull($command->getExcludedRoadBanId());
        $this->assertInstanceOf(GetZoneGeometryQuery::class, $command->getGeometryQuery());
    }

    public function testCleanDropsInactiveSubCommandsForNumberedRoad(): void
    {
        $command = new SaveWholeCityExceptionCommand();
        $command->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $command->departmentalRoad = new SaveNumberedRoadCommand();
        $command->departmentalRoad->roadType = RoadTypeEnum::DEPARTMENTAL_ROAD->value;
        $command->departmentalRoad->roadNumber = 'D110';
        $command->departmentalRoad->fromPointNumberWithDepartmentCode = '07##6';
        $command->departmentalRoad->toPointNumberWithDepartmentCode = '07##15';
        $command->nationalRoad = new SaveNumberedRoadCommand();
        $command->nationalRoad->roadNumber = 'leftover';
        $command->namedStreet = new SaveNamedStreetCommand();
        $command->zone = new SaveZoneCommand();
        $command->rawGeoJSON = new SaveRawGeoJSONCommand();

        $command->clean();

        $this->assertNotNull($command->departmentalRoad);
        $this->assertNull($command->nationalRoad);
        $this->assertNull($command->namedStreet);
        $this->assertNull($command->zone);
        $this->assertNull($command->rawGeoJSON);
        // clean() décode les points de repère saisis via le champ encodé.
        $this->assertSame('07', $command->departmentalRoad->fromDepartmentCode);
        $this->assertSame('6', $command->departmentalRoad->fromPointNumber);
        $this->assertSame('15', $command->departmentalRoad->toPointNumber);
    }

    public function testCleanDropsInactiveSubCommandsForZone(): void
    {
        $command = new SaveWholeCityExceptionCommand();
        $command->roadType = RoadTypeEnum::ZONE->value;
        $command->zone = new SaveZoneCommand();
        $command->zone->geometry = '<polygone>';
        $command->namedStreet = new SaveNamedStreetCommand();
        $command->rawGeoJSON = new SaveRawGeoJSONCommand();

        $command->clean();

        $this->assertNotNull($command->zone);
        $this->assertNull($command->namedStreet);
        $this->assertNull($command->rawGeoJSON);
        $this->assertSame($command->zone, $command->getActiveRoadCommand());
    }

    public function testNumberedRoadRoundTrip(): void
    {
        $original = new SaveWholeCityExceptionCommand();
        $original->roadType = RoadTypeEnum::NATIONAL_ROAD->value;
        $original->nationalRoad = new SaveNumberedRoadCommand();
        $original->nationalRoad->roadType = RoadTypeEnum::NATIONAL_ROAD->value;
        $original->nationalRoad->administrator = 'DIR Ouest';
        $original->nationalRoad->roadNumber = 'N176';
        $original->nationalRoad->fromPointNumberWithDepartmentCode = '22##1';
        $original->nationalRoad->toPointNumberWithDepartmentCode = '22##2';
        $original->nationalRoad->fromSide = 'D';
        $original->nationalRoad->toSide = 'D';
        $original->clean();

        $persisted = new WholeCityException(
            uuid: 'uuid',
            location: $this->createMock(\App\Domain\Regulation\Location\Location::class),
            roadType: RoadTypeEnum::NATIONAL_ROAD->value,
            label: $original->getLabel(),
            geometry: '<geom>',
            data: $original->toData(),
        );

        $reloaded = new SaveWholeCityExceptionCommand($persisted);

        // La signature (roadType + toData) doit être stable pour éviter les recalculs de géométrie.
        $this->assertSame($original->toData(), $reloaded->toData());
        $this->assertSame('22', $reloaded->nationalRoad->fromDepartmentCode);
        $this->assertSame('1', $reloaded->nationalRoad->fromPointNumber);
        $this->assertSame('22##1', $reloaded->nationalRoad->fromPointNumberWithDepartmentCode);
    }

    public function testToDataForZone(): void
    {
        $command = new SaveWholeCityExceptionCommand();
        $command->roadType = RoadTypeEnum::ZONE->value;
        $command->zone = new SaveZoneCommand();
        $command->zone->label = 'Quartier des Halles';
        $command->zone->geometry = '<polygone>';

        $this->assertSame(
            ['label' => 'Quartier des Halles', 'geometry' => '<polygone>'],
            $command->toData(),
        );
    }
}
