<?php

declare(strict_types=1);

namespace App\Application\VisaModel\Command;

use App\Application\CommandInterface;
use App\Domain\User\Organization;

final class SaveVisaModelCommand implements CommandInterface
{
    public ?string $name = null;
    public ?string $description = null;
    public array $visas = [];

    public function __construct(
        public readonly Organization $organization,
    ) {
    }
}
