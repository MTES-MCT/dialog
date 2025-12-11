<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Application\CommandInterface;
use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Period;

final class SaveDailyRangeCommand implements CommandInterface
{
    public ?string $recurrenceType = null;
    public array $applicableDays = [];
    public ?Period $period;

    public function __construct(
        public readonly ?DailyRange $dailyRange = null,
    ) {
        $this->initFromEntity($dailyRange);
    }

    public function initFromEntity(?DailyRange $dailyRange): self
    {
        $this->applicableDays = $dailyRange?->getApplicableDays() ?? [];

        return $this;
    }

    public function sortApplicableDays(): void
    {
        usort($this->applicableDays, function (string $d1, string $d2) {
            return ApplicableDayEnum::getDayIndex($d1) <=> ApplicableDayEnum::getDayIndex($d2);
        });
    }
}
