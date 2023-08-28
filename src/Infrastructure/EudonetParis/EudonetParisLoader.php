<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

use App\Application\CommandBusInterface;
use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;

final class EudonetParisLoader
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function load(ImportEudonetParisRegulationCommand $command): void
    {
        $this->commandBus->handle($command);
    }
}
