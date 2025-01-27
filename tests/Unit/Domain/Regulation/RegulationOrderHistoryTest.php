<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Regulation\Enum\ActionTypeEnum;
use App\Domain\Regulation\RegulationOrderHistory;
use PHPUnit\Framework\TestCase;

final class RegulationOrderHistoryTest extends TestCase
{
    public function testGetters(): void
    {
        $date = new \DateTimeImmutable('2023-11-02 00:00:00');
        $regulationOrderHistory = new RegulationOrderHistory(
            uuid: '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            regulationOrderUuid: 'f518b777-aa4e-4e8e-852b-15a64c849198',
            userUuid: '6659bfb5-1e51-4000-99de-68e3d9a90a69',
            action: ActionTypeEnum::CREATE->value,
            date: $date,
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $regulationOrderHistory->getUuid());
        $this->assertSame('f518b777-aa4e-4e8e-852b-15a64c849198', $regulationOrderHistory->getRegulationOrderUuid());
        $this->assertSame('6659bfb5-1e51-4000-99de-68e3d9a90a69', $regulationOrderHistory->getUserUuid());
        $this->assertSame('create', $regulationOrderHistory->getAction());
        $this->assertSame($date, $regulationOrderHistory->getDate());
    }
}
