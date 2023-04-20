<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;
use Symfony\Contracts\Translation\TranslatorInterface;

// TODO : Duplicate locations
final class DuplicateRegulationCommandHandler
{
    public function __construct(
        private TranslatorInterface $translator,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(DuplicateRegulationCommand $command): RegulationOrderRecord
    {
        $organization = $command->organization;
        $originalRegulationOrderRecord = $command->originalRegulationOrderRecord;
        $originalRegulationOrder = $originalRegulationOrderRecord->getRegulationOrder();

        $duplicatedRegulationOrderRecord = $this->duplicateRegulationOrderRecord($organization, $originalRegulationOrder);

        return $duplicatedRegulationOrderRecord;
    }

    private function duplicateRegulationOrderRecord(
        Organization $organization,
        RegulationOrder $originalRegulationOrder,
    ): RegulationOrderRecord {
        $step1Command = new SaveRegulationGeneralInfoCommand();
        $step1Command->organization = $organization;
        $step1Command->identifier = $this->translator->trans('regulation.identifier.copy', [
            '%identifier%' => $originalRegulationOrder->getIdentifier(),
        ]);
        $step1Command->description = $originalRegulationOrder->getDescription();
        $step1Command->startDate = $originalRegulationOrder->getStartDate();
        $step1Command->endDate = $originalRegulationOrder->getEndDate();

        return $this->commandBus->handle($step1Command);
    }
}
