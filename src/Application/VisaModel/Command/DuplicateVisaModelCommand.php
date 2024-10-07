<?php

declare(strict_types=1);

namespace App\Application\VisaModel\Command;

use App\Application\CommandInterface;
use App\Domain\User\Organization;

final readonly class DuplicateVisaModelCommand implements CommandInterface
{
    public function __construct(
        public Organization $organization,
        public string $uuid,
    ) {
    }
}
