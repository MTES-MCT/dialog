<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\RegulationOrderTemplate;

use App\Application\CommandInterface;

final class DeleteRegulationOrderTemplateCommand implements CommandInterface
{
    public function __construct(
        public readonly string $uuid,
    ) {
    }
}
