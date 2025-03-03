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

    public function testDecodePointNumbers(): void
    {
        $command = new SaveNumberedRoadCommand();

        $command->fromPointNumberValue = '11##122';
        $command->toPointNumberValue = '12##123';

        $command->clean();

        $this->assertSame('11', $command->fromDepartmentCode);
        $this->assertSame('122', $command->fromPointNumber);
        $this->assertSame('12', $command->toDepartmentCode);
        $this->assertSame('123', $command->toPointNumber);
    }
}
