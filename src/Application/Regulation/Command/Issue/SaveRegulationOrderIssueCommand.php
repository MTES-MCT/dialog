<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Issue;

use App\Application\CommandInterface;
use App\Domain\User\Organization;

final readonly class SaveRegulationOrderIssueCommand implements CommandInterface
{
    public function __construct(
        public string $identifier,
        public string $level,
        public string $source,
        public string $context,
        public Organization $organization,
        public ?string $geometry = null,
    ) {
    }
}
