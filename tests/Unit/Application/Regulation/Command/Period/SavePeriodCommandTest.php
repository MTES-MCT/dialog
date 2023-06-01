<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Period;

use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use PHPUnit\Framework\TestCase;

final class SavePeriodCommandTest extends TestCase
{
    public function testSortApplicableDays(): void
    {
        $command = new SavePeriodCommand();
        $command->applicableDays = [
            ApplicableDayEnum::SUNDAY->value,
            ApplicableDayEnum::WEDNESDAY->value,
            ApplicableDayEnum::THURSDAY->value,
            ApplicableDayEnum::MONDAY->value,
        ];
        $command->sortApplicableDays();

        $this->assertSame($command->applicableDays, [
            ApplicableDayEnum::MONDAY->value,
            ApplicableDayEnum::WEDNESDAY->value,
            ApplicableDayEnum::THURSDAY->value,
            ApplicableDayEnum::SUNDAY->value,
        ]);
    }
}
