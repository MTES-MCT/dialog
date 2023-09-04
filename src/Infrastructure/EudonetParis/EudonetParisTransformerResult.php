<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;

final class EudonetParisTransformerResult
{
    public function __construct(
        public readonly ?ImportEudonetParisRegulationCommand $command,
        public readonly array $skipMessages,
    ) {
    }
}
