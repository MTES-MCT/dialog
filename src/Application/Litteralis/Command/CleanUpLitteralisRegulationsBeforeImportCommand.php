<?php

declare(strict_types=1);

namespace App\Application\Litteralis\Command;

use App\Application\CommandInterface;

final readonly class CleanUpLitteralisRegulationsBeforeImportCommand implements CommandInterface
{
    public function __construct(
        public string $organizationId,
        public \DateTimeInterface $laterThan,
    ) {
    }
}
