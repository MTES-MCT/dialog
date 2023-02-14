<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\CommandInterface;

final class SaveRegulationStep5Command implements CommandInterface
{
    public function __construct(
        public readonly string $regulationOrderUuid,
        public string $status,
    ) {
    }
}
