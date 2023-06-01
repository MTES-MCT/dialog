<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Application\Regulation\Command\Condition\SavePeriodCommand;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Measure;

final class SaveMeasureCommand implements CommandInterface
{
    public ?string $type;
    public ?Location $location;
    public array $periods = [];

    public function __construct(
        public readonly ?Measure $measure = null,
    ) {
        $this->location = $measure?->getLocation();
        $this->type = $measure?->getType();

        if ($measure) {
            foreach ($measure->getConditions() as $condition) {
                if ($condition->getPeriod()) {
                    $this->periods[] = new SavePeriodCommand($condition->getPeriod());

                    continue;
                }
            }
        }
    }
}
