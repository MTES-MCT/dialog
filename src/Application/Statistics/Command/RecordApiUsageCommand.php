<?php

declare(strict_types=1);

namespace App\Application\Statistics\Command;

use App\Application\CommandInterface;

final class RecordApiUsageCommand implements CommandInterface
{
    public function __construct(
        public readonly string $type,
    ) {
    }
}
