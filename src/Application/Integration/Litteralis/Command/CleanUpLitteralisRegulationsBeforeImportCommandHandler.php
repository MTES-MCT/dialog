<?php

declare(strict_types=1);

namespace App\Application\Integration\Litteralis\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\DeleteRegulationCommand;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;

final class CleanUpLitteralisRegulationsBeforeImportCommandHandler
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
    ) {
    }

    public function __invoke(CleanUpLitteralisRegulationsBeforeImportCommand $command): void
    {
        $regulationOrderRecords = $this->regulationOrderRecordRepository->findRegulationOrdersForLitteralisCleanUp(
            $command->organizationId,
            $command->laterThan,
        );

        foreach ($regulationOrderRecords as $regulationOrderRecord) {
            $this->commandBus->handle(new DeleteRegulationCommand([$command->organizationId], $regulationOrderRecord));
        }
    }
}
