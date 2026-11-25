<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\View\Measure;

use App\Application\Regulation\View\Measure\LocationView;
use App\Application\Regulation\View\Measure\NamedStreetView;
use App\Application\Regulation\View\Measure\NumberedRoadView;
use App\Application\Regulation\View\Measure\RawGeoJSONView;
use App\Application\Regulation\View\Measure\WholeCityView;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use PHPUnit\Framework\TestCase;

final class LocationViewTest extends TestCase
{
    public function testDirectionOfNamedStreet(): void
    {
        $view = new LocationView(
            uuid: '065f94ef-ea0a-7ab5-8000-bd5686102151',
            roadType: RoadTypeEnum::LANE->value,
            namedStreet: new NamedStreetView(
                cityLabel: 'Saint-Ouen-sur-Seine',
                roadName: 'Rue Eugène Berthoud',
                fromHouseNumber: '47',
                fromRoadName: null,
                toHouseNumber: '65',
                toRoadName: null,
                direction: DirectionEnum::A_TO_B->value,
            ),
        );

        $this->assertSame('A_TO_B', $view->getDirection());
    }

    public function testDirectionOfNumberedRoad(): void
    {
        $view = new LocationView(
            uuid: '065f94ef-ea0a-7ab5-8000-bd5686102151',
            roadType: RoadTypeEnum::DEPARTMENTAL_ROAD->value,
            numberedRoad: new NumberedRoadView(
                administrator: 'Ardennes',
                roadNumber: 'D322',
                fromPointNumber: '1',
                fromAbscissa: 0,
                fromSide: 'U',
                toPointNumber: '4',
                toAbscissa: 0,
                toSide: 'U',
                direction: DirectionEnum::B_TO_A->value,
            ),
        );

        $this->assertSame('B_TO_A', $view->getDirection());
    }

    public function testDirectionOfRawGeoJSON(): void
    {
        $view = new LocationView(
            uuid: '065f94ef-ea0a-7ab5-8000-bd5686102151',
            roadType: RoadTypeEnum::RAW_GEOJSON->value,
            rawGeoJSON: new RawGeoJSONView(label: 'Tracé', direction: DirectionEnum::BOTH->value),
        );

        $this->assertSame('BOTH', $view->getDirection());
    }

    public function testNoDirectionForWholeCity(): void
    {
        $view = new LocationView(
            uuid: '065f94ef-ea0a-7ab5-8000-bd5686102151',
            roadType: RoadTypeEnum::WHOLE_CITY->value,
            wholeCity: new WholeCityView(cityCode: '93070', cityLabel: 'Saint-Ouen-sur-Seine'),
        );

        $this->assertNull($view->getDirection());
    }
}
