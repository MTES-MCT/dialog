<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Condition\Query\Location\GetLocationByRegulationConditionQuery;
use App\Application\Condition\Query\Period\GetOverallPeriodByRegulationConditionQuery;
use App\Application\Condition\Query\VehicleCharacteristics\GetVehicleCharacteristicsByRegulationConditionQuery;
use App\Application\IdFactoryInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Steps\SaveRegulationStep1Command;
use App\Application\Regulation\Command\Steps\SaveRegulationStep3Command;
use App\Application\Regulation\Command\Steps\SaveRegulationStep4Command;
use App\Domain\Condition\Factory\LocationFactory;
use App\Domain\Condition\Location;
use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Condition\RegulationCondition;
use App\Domain\Condition\Repository\LocationRepositoryInterface;
use App\Domain\Condition\VehicleCharacteristics;
use App\Domain\Regulation\Exception\RegulationCannotBeDuplicated;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Specification\CanRegulationBeDuplicated;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DuplicateRegulationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private TranslatorInterface $translator,
        private CanRegulationBeDuplicated $canRegulationBeDuplicated,
        private QueryBusInterface $queryBus,
        private CommandBusInterface $commandBus,
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    public function __invoke(DuplicateRegulationCommand $command): RegulationOrderRecord
    {
        $organization = $command->user->getOrganization();
        $originalRegulationOrderRecord = $command->originalRegulationOrderRecord;
        $originalRegulationOrder = $originalRegulationOrderRecord->getRegulationOrder();
        $originalRegulationCondition = $originalRegulationOrder->getRegulationCondition();

        if (!$this->canRegulationBeDuplicated->isSatisfiedBy($originalRegulationOrderRecord, $organization)) {
            throw new RegulationCannotBeDuplicated();
        }

        $duplicatedRegulationOrderRecord = $this->duplicateRegulationOrderRecord($command->user, $originalRegulationOrder);
        $duplicatedRegulationCondition = $duplicatedRegulationOrderRecord->getRegulationOrder()->getRegulationCondition();

        $this->duplicateLocation($originalRegulationCondition, $duplicatedRegulationCondition);
        $this->duplicateOverallPeriod($originalRegulationCondition, $duplicatedRegulationOrderRecord);
        $this->duplicateVehicleCharacteristics($originalRegulationCondition, $duplicatedRegulationOrderRecord);

        return $duplicatedRegulationOrderRecord;
    }

    private function duplicateRegulationOrderRecord(
        SymfonyUser $user,
        RegulationOrder $originalRegulationOrder,
    ): RegulationOrderRecord {
        $step1Command = new SaveRegulationStep1Command($user);
        $step1Command->issuingAuthority = $originalRegulationOrder->getIssuingAuthority();
        $step1Command->description = $this->translator->trans('regulation.description.copy', [
            '%description%' => $originalRegulationOrder->getDescription(),
        ]);

        return $this->commandBus->handle($step1Command);
    }

    private function duplicateLocation(
        RegulationCondition $originalRegulationCondition,
        RegulationCondition $duplicatedRegulationCondition,
    ): void {
        $location = $this->queryBus->handle(
            new GetLocationByRegulationConditionQuery($originalRegulationCondition->getUuid()),
        );

        if ($location instanceof Location) {
            $this->locationRepository->save(
                LocationFactory::duplicate(
                    $this->idFactory->make(),
                    $duplicatedRegulationCondition,
                    $location,
                ),
            );
        }
    }

    private function duplicateOverallPeriod(
        RegulationCondition $originalRegulationCondition,
        RegulationOrderRecord $duplicatedRegulationOrderRecord,
    ): void {
        $overallPeriod = $this->queryBus->handle(
            new GetOverallPeriodByRegulationConditionQuery($originalRegulationCondition->getUuid()),
        );

        if ($overallPeriod instanceof OverallPeriod) {
            $step3Command = new SaveRegulationStep3Command($duplicatedRegulationOrderRecord);
            $step3Command->startPeriod = $overallPeriod->getStartPeriod();
            $step3Command->endPeriod = $overallPeriod->getEndPeriod();
            $this->commandBus->handle($step3Command);
        }
    }

    private function duplicateVehicleCharacteristics(
        RegulationCondition $originalRegulationCondition,
        RegulationOrderRecord $duplicatedRegulationOrderRecord,
    ): void {
        $vehicleCharacteristics = $this->queryBus->handle(
            new GetVehicleCharacteristicsByRegulationConditionQuery($originalRegulationCondition->getUuid()),
        );

        if ($vehicleCharacteristics instanceof VehicleCharacteristics) {
            $step4Command = new SaveRegulationStep4Command($duplicatedRegulationOrderRecord);
            $step4Command->maxHeight = $vehicleCharacteristics->getMaxHeight();
            $step4Command->maxLength = $vehicleCharacteristics->getMaxLength();
            $step4Command->maxWeight = $vehicleCharacteristics->getMaxWeight();
            $step4Command->maxWidth = $vehicleCharacteristics->getMaxWidth();
            $this->commandBus->handle($step4Command);
        }
    }
}
