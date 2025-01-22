<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\EudonetParis;

use App\Application\Integration\EudonetParis\Command\ImportEudonetParisRegulationCommand;

final class EudonetParisTransformerResult
{
    public function __construct(
        public readonly ?ImportEudonetParisRegulationCommand $command,
        public readonly array $errors,
    ) {
    }
}
