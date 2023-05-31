<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Period\DeletePeriodCommand;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;

final class SaveMeasureCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private MeasureRepositoryInterface $measureRepository,
        private CommandBusInterface $commandBus,
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(SaveMeasureCommand $command): Measure
    {
        $command->cleanVehicleTypes();

        if ($command->measure) {
            $command->measure->update(
                $command->type,
                $command->restrictedVehicleTypes,
                $command->otherRestrictedVehicleTypeText,
                $command->exemptedVehicleTypes,
                $command->otherExemptedVehicleTypeText,
            );

            $periodsStillPresentUuids = [];

            // Periods provided with the command get created or updated...
            foreach ($command->periods as $periodCommand) {
                if ($periodCommand->period) {
                    $periodsStillPresentUuids[] = $periodCommand->period->getUuid();
                }

                $periodCommand->measure = $command->measure;
                $period = $this->commandBus->handle($periodCommand);
            }

            // Periods that were not present in the command can deleted.
            foreach ($command->measure->getPeriods() as $period) {
                if (!\in_array($period->getUuid(), $periodsStillPresentUuids)) {
                    $this->commandBus->handle(new DeletePeriodCommand($period));
                    $command->measure->removePeriod($period);
                }
            }

            return $command->measure;
        }

        $measure = $this->measureRepository->add(
            new Measure(
                uuid: $this->idFactory->make(),
                location: $command->location,
                type: $command->type,
                createdAt: $command->createdAt ?? $this->dateUtils->getNow(),
                restrictedVehicleTypes: $command->restrictedVehicleTypes,
                otherRestrictedVehicleTypeText: $command->otherRestrictedVehicleTypeText,
                exemptedVehicleTypes: $command->exemptedVehicleTypes,
                otherExemptedVehicleTypeText: $command->otherExemptedVehicleTypeText,
            ),
        );

        foreach ($command->periods as $periodCommand) {
            $periodCommand->measure = $measure;
            $period = $this->commandBus->handle($periodCommand);
            $measure->addPeriod($period);
        }

        return $measure;
    }
}
