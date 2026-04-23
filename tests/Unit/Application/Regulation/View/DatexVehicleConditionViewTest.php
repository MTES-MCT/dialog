<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\View;

use App\Application\Regulation\View\DatexVehicleConditionView;
use App\Domain\Regulation\Enum\CritairEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use PHPUnit\Framework\TestCase;

final class DatexVehicleConditionViewTest extends TestCase
{
    public function testPedestrians(): void
    {
        $view = new DatexVehicleConditionView(VehicleTypeEnum::PEDESTRIANS->value, isExempted: true);

        $this->assertSame(VehicleTypeEnum::PEDESTRIANS->value, $view->nonVehicularRoadUser);
        $this->assertNull($view->type);
        $this->assertNull($view->vehicleUsage);
        $this->assertFalse($view->isOther);
        $this->assertTrue($view->isExempted);
    }

    public function testEmergencyServices(): void
    {
        $view = new DatexVehicleConditionView(VehicleTypeEnum::EMERGENCY_SERVICES->value, isExempted: true);

        $this->assertSame(VehicleTypeEnum::EMERGENCY_SERVICES->value, $view->vehicleUsage);
        $this->assertNull($view->type);
    }

    public function testRoadMaintenanceOrConstruction(): void
    {
        $view = new DatexVehicleConditionView(VehicleTypeEnum::ROAD_MAINTENANCE_OR_CONSTRUCTION->value, isExempted: true);

        $this->assertSame('roadMaintenanceOrConstruction', $view->vehicleUsage);
        $this->assertNull($view->type);
        $this->assertFalse($view->isOther);
    }

    public function testPatrol(): void
    {
        $view = new DatexVehicleConditionView(VehicleTypeEnum::PATROL->value, isExempted: true);

        $this->assertSame('patrol', $view->vehicleUsage);
        $this->assertNull($view->type);
        $this->assertFalse($view->isOther);
    }

    public function testDesserteLocale(): void
    {
        $view = new DatexVehicleConditionView(
            VehicleTypeEnum::DESSERTE_LOCALE->value,
            isExempted: true,
            otherTypeText: 'Desserte locale',
        );

        $this->assertTrue($view->isOther);
        $this->assertSame('Desserte locale', $view->otherTypeText);
        $this->assertNull($view->type);
        $this->assertNull($view->vehicleUsage);
    }

    public function testCritair(): void
    {
        $view = new DatexVehicleConditionView(CritairEnum::CRITAIR_3->value);

        $this->assertSame(CritairEnum::CRITAIR_3->value, $view->emissionClassificationOther);
    }

    public function testOther(): void
    {
        $view = new DatexVehicleConditionView(VehicleTypeEnum::OTHER->value, otherTypeText: 'Trottinettes');

        $this->assertTrue($view->isOther);
        $this->assertSame('Trottinettes', $view->otherTypeText);
        $this->assertNull($view->type);
    }

    public function testHazardousMaterials(): void
    {
        $view = new DatexVehicleConditionView(VehicleTypeEnum::HAZARDOUS_MATERIALS->value);

        $this->assertTrue($view->isDangerousSubstances);
        $this->assertNull($view->type);
    }

    public function testDefaultFallsThroughToType(): void
    {
        $view = new DatexVehicleConditionView(VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value, maxWeight: 3.5);

        $this->assertSame('heavyGoodsVehicle', $view->type);
        $this->assertSame(3.5, $view->maxWeight);
    }
}
