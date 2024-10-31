<?php

declare(strict_types=1);

namespace App\Application\Organization\VisaModel\Command;

use App\Application\CommandInterface;

final class DeleteVisaModelCommand implements CommandInterface
{
    public function __construct(
        public readonly string $uuid,
    ) {
    }
}
