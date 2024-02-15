<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Condition;

use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Enum\CritairEnum;
use App\Domain\Regulation\Measure;
use PHPUnit\Framework\TestCase;

final class VehicleSetTest extends TestCase
{
    public function testGetters(): void
    {
        $measure = $this->createMock(Measure::class);

        $vehicleSet = new VehicleSet(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $measure,
            restrictedTypes: ['heavyGoodsVehicle'],
            otherRestrictedTypeText: null,
            exemptedTypes: ['commercial', 'other'],
            otherExemptedTypeText: 'Convois exceptionnels',
            heavyweightMaxWeight: 3.5,
            maxLength: 12,
            critairTypes: [CritairEnum::CRITAIR_3->value, CritairEnum::CRITAIR_2->value],
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $vehicleSet->getUuid());
        $this->assertSame(['heavyGoodsVehicle'], $vehicleSet->getRestrictedTypes());
        $this->assertNull($vehicleSet->getOtherRestrictedTypeText());
        $this->assertSame(['commercial', 'other'], $vehicleSet->getExemptedTypes());
        $this->assertSame('Convois exceptionnels', $vehicleSet->getOtherExemptedTypeText());
        $this->assertSame($measure, $vehicleSet->getMeasure());
        $this->assertSame(3.5, $vehicleSet->getHeavyweightMaxWeight());
        $this->assertNull($vehicleSet->getMaxWidth());
        $this->assertSame(12.0, $vehicleSet->getMaxLength());
        $this->assertNull($vehicleSet->getMaxHeight());
        $critairTypes = $vehicleSet->getCritairTypes();
        $this->assertEquals([CritairEnum::CRITAIR_2->value, CritairEnum::CRITAIR_3->value], $critairTypes);
        // Check does not return mutable array
        $critairTypes[0] = CritairEnum::CRITAIR_4;
        $this->assertEquals([CritairEnum::CRITAIR_2->value, CritairEnum::CRITAIR_3->value], $vehicleSet->getCritairTypes());
    }

    public function testUpdate(): void
    {
        $measure = $this->createMock(Measure::class);

        $vehicleSet = new VehicleSet(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $measure,
            restrictedTypes: ['heavyGoodsVehicle'],
            otherRestrictedTypeText: null,
            exemptedTypes: ['commercial', 'other'],
            otherExemptedTypeText: 'Convois exceptionnels',
            heavyweightMaxWeight: 3.5,
            maxLength: 12,
            critairTypes: [CritairEnum::CRITAIR_2->value, CritairEnum::CRITAIR_3->value],
        );

        $vehicleSet->update(
            restrictedTypes: ['other'],
            otherRestrictedTypeText: 'Charettes à bras',
            exemptedTypes: null,
            otherExemptedTypeText: null,
            heavyweightMaxWeight: 5.5,
            maxWidth: 3,
            critairTypes: null,
        );
        $this->assertSame(['other'], $vehicleSet->getRestrictedTypes());
        $this->assertSame('Charettes à bras', $vehicleSet->getOtherRestrictedTypeText());
        $this->assertSame([], $vehicleSet->getExemptedTypes());
        $this->assertSame([], $vehicleSet->getCritairTypes());
        $this->assertNull($vehicleSet->getOtherExemptedTypeText());
        $this->assertSame(5.5, $vehicleSet->getHeavyweightMaxWeight());
        $this->assertSame(3.0, $vehicleSet->getMaxWidth());
        $this->assertNull($vehicleSet->getMaxLength());
        $this->assertNull($vehicleSet->getMaxHeight());
    }
}
