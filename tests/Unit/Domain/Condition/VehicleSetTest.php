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
            exemptedTypes: ['bus', 'other'],
            otherExemptedTypeText: 'Convois exceptionnels',
            critairTypes: [CritairEnum::CRITAIR_2, CritairEnum::CRITAIR_3],
        );

        $this->assertSame('9f3cbc01-8dbe-4306-9912-91c8d88e194f', $vehicleSet->getUuid());
        $this->assertSame(['heavyGoodsVehicle'], $vehicleSet->getRestrictedTypes());
        $this->assertNull($vehicleSet->getOtherRestrictedTypeText());
        $this->assertSame(['bus', 'other'], $vehicleSet->getExemptedTypes());
        $this->assertSame('Convois exceptionnels', $vehicleSet->getOtherExemptedTypeText());
        $this->assertSame($measure, $vehicleSet->getMeasure());
        $this->assertSame([CritairEnum::CRITAIR_2, CritairEnum::CRITAIR_3], $vehicleSet->getCritairTypes());
    }

    public function testUpdate(): void
    {
        $measure = $this->createMock(Measure::class);

        $vehicleSet = new VehicleSet(
            '9f3cbc01-8dbe-4306-9912-91c8d88e194f',
            $measure,
            restrictedTypes: ['heavyGoodsVehicle'],
            otherRestrictedTypeText: null,
            exemptedTypes: ['bus', 'other'],
            otherExemptedTypeText: 'Convois exceptionnels',
            critairTypes: [CritairEnum::CRITAIR_2, CritairEnum::CRITAIR_3],
        );

        $vehicleSet->update(
            restrictedTypes: ['other'],
            otherRestrictedTypeText: 'Charettes à bras',
            exemptedTypes: null,
            otherExemptedTypeText: null,
            critairTypes: null,
        );
        $this->assertSame(['other'], $vehicleSet->getRestrictedTypes());
        $this->assertSame('Charettes à bras', $vehicleSet->getOtherRestrictedTypeText());
        $this->assertSame([], $vehicleSet->getExemptedTypes());
        $this->assertSame([], $vehicleSet->getCritairTypes());
        $this->assertNull($vehicleSet->getOtherExemptedTypeText());
    }
}
