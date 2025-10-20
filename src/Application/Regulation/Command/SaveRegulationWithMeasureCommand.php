<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Infrastructure\DTO\Event\SaveMeasureDTO;

final class SaveRegulationWithMeasureCommand implements CommandInterface
{
    public function __construct(
        public SaveRegulationGeneralInfoCommand $generalInfo,
        public ?SaveMeasureDTO $measureDto,
    ) {
    }
}
