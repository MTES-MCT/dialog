<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\RegulationOrderTemplate;

use App\Application\CommandInterface;
use App\Domain\User\Organization;

final readonly class DuplicateRegulationOrderTemplateCommand implements CommandInterface
{
    public function __construct(
        public Organization $organization,
        public string $uuid,
    ) {
    }
}
