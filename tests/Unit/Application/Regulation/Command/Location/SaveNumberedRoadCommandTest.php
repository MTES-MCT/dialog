<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Domain\Regulation\Location\NumberedRoad;
use PHPUnit\Framework\TestCase;

final class SaveNumberedRoadCommandTest extends TestCase
{
    private function provideEncodePointNumbers(): array
    {
        return [
            'all-values' => ['07', '12', '08', '2', '07##12', '12 (07)', '08##2', '2 (08)'],
            'departmentCode-null' => [null, '12', null, '2', '12', '12', '2', '2'], // For historical data
            'pointNumber-null' => ['07', null, '08', null, null, null, null, null], // For empty data
            'pointNumber-zero' => ['0', null, '00', null, null, null, null, null], // Special regression test case, avoid use of empty('0')
        ];
    }

    /**
     * @dataProvider provideEncodePointNumbers
     */
    public function testEncodePointNumbers(
        ?string $fromDepartmentCode,
        ?string $fromPointNumber,
        ?string $toDepartmentCode,
        ?string $toPointNumber,
        ?string $expectedFromPointNumberValue,
        ?string $expectedFromPointNumberDisplayedValue,
        ?string $expectedToPointNumberValue,
        ?string $expectedToPointNumberDisplayedValue,
    ): void {
        $numberedRoad = $this->createMock(NumberedRoad::class);

        $numberedRoad
            ->expects(self::once())
            ->method('getFromDepartmentCode')
            ->willReturn($fromDepartmentCode);

        $numberedRoad
            ->expects(self::once())
            ->method('getFromPointNumber')
            ->willReturn($fromPointNumber);

        $numberedRoad
            ->expects(self::once())
            ->method('getToDepartmentCode')
            ->willReturn($toDepartmentCode);

        $numberedRoad
            ->expects(self::once())
            ->method('getToPointNumber')
            ->willReturn($toPointNumber);

        $command = new SaveNumberedRoadCommand($numberedRoad);

        $this->assertSame($expectedFromPointNumberValue, $command->fromPointNumberValue);
        $this->assertSame($expectedFromPointNumberDisplayedValue, $command->fromPointNumberDisplayedValue);
        $this->assertSame($expectedToPointNumberValue, $command->toPointNumberValue);
        $this->assertSame($expectedToPointNumberDisplayedValue, $command->toPointNumberDisplayedValue);
    }

    private function provideDecodePointNumbers(): array
    {
        return [
            'all' => ['11##123', '08##4', '11', '123', '08', '4'],
            'pr-only' => ['123', '4', null, '123', null, '4'],
            'pr-zero' => ['0', '00', null, '0', null, '00'],
            'pr-empty-or-null' => ['', null, null, null, null, null],
        ];
    }

    /**
     * @dataProvider provideDecodePointNumbers
     */
    public function testDecodePointNumbers(
        ?string $fromPointNumberValue,
        ?string $toPointNumberValue,
        ?string $expectedFromDepartmentCode,
        ?string $expectedFromPointNumber,
        ?string $expectedToDepartmentcode,
        ?string $expectedToPointNumber,
    ): void {
        $command = new SaveNumberedRoadCommand();

        $command->fromPointNumberValue = $fromPointNumberValue;
        $command->toPointNumberValue = $toPointNumberValue;

        $command->clean();

        $this->assertSame($expectedFromDepartmentCode, $command->fromDepartmentCode);
        $this->assertSame($expectedFromPointNumber, $command->fromPointNumber);
        $this->assertSame($expectedToDepartmentcode, $command->toDepartmentCode);
        $this->assertSame($expectedToPointNumber, $command->toPointNumber);
    }
}
