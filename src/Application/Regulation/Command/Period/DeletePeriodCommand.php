<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Period;

use App\Application\CommandInterface;
use App\Domain\Condition\Period\Period;

final class DeletePeriodCommand implements CommandInterface
{
    public function __construct(
        public readonly Period $period,
    ) {
    }
}
