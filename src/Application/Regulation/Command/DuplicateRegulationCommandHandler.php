<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Condition\Query\Period\GetOverallPeriodByRegulationConditionQuery;
use App\Application\Condition\Query\VehicleCharacteristics\GetVehicleCharacteristicsByRegulationConditionQuery;
use App\Application\IdFactoryInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Steps\SaveRegulationStep1Command;
use App\Application\Regulation\Command\Steps\SaveRegulationStep3Command;
use App\Application\Regulation\Command\Steps\SaveRegulationStep4Command;
use App\Application\Regulation\Query\Location\GetLocationByRegulationOrderQuery;
use App\Domain\Condition\Period\OverallPeriod;
use App\Domain\Condition\RegulationCondition;
use App\Domain\Condition\VehicleCharacteristics;
use App\Domain\Regulation\Exception\RegulationCannotBeDuplicated;
use App\Domain\Regulation\Factory\LocationFactory;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Domain\User\Organization;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DuplicateRegulationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private TranslatorInterface $translator,
        private CanOrganizationAccessToRegulation $canOrganizationAccessToRegulation,
        private QueryBusInterface $queryBus,
        private CommandBusInterface $commandBus,
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    public function __invoke(DuplicateRegulationCommand $command): RegulationOrderRecord
    {
        $organization = $command->organization;
        $originalRegulationOrderRecord = $command->originalRegulationOrderRecord;
        $originalRegulationOrder = $originalRegulationOrderRecord->getRegulationOrder();
        $originalRegulationCondition = $originalRegulationOrder->getRegulationCondition();

        if (!$this->canOrganizationAccessToRegulation->isSatisfiedBy($originalRegulationOrderRecord, $organization)) {
            throw new RegulationCannotBeDuplicated();
        }

        $duplicatedRegulationOrderRecord = $this->duplicateRegulationOrderRecord($organization, $originalRegulationOrder);
        $duplicatedRegulationOrder = $duplicatedRegulationOrderRecord->getRegulationOrder();

        $this->duplicateLocation($originalRegulationOrder, $duplicatedRegulationOrder);
        $this->duplicateOverallPeriod($originalRegulationCondition, $duplicatedRegulationOrderRecord);
        $this->duplicateVehicleCharacteristics($originalRegulationCondition, $duplicatedRegulationOrderRecord);

        return $duplicatedRegulationOrderRecord;
    }

    private function duplicateRegulationOrderRecord(
        Organization $organization,
        RegulationOrder $originalRegulationOrder,
    ): RegulationOrderRecord {
        $step1Command = new SaveRegulationStep1Command($organization);
        $step1Command->issuingAuthority = $originalRegulationOrder->getIssuingAuthority();
        $step1Command->description = $this->translator->trans('regulation.description.copy', [
            '%description%' => $originalRegulationOrder->getDescription(),
        ]);
        $step1Command->startDate = $originalRegulationOrder->getStartDate();
        $step1Command->endDate = $originalRegulationOrder->getEndDate();

        return $this->commandBus->handle($step1Command);
    }

    private function duplicateLocation(
        RegulationOrder $originalRegulationOrder,
        RegulationOrder $duplicatedRegulationOrder,
    ): void {
        $location = $this->queryBus->handle(
            new GetLocationByRegulationOrderQuery($originalRegulationOrder->getUuid()),
        );

        if ($location instanceof Location) {
            $this->locationRepository->save(
                LocationFactory::duplicate(
                    $this->idFactory->make(),
                    $duplicatedRegulationOrder,
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
            $step3Command->startDate = $overallPeriod->getStartDate();
            $step3Command->startTime = $overallPeriod->getStartTime();
            $step3Command->endDate = $overallPeriod->getEndDate();
            $step3Command->endTime = $overallPeriod->getEndTime();
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
