<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\IdFactoryInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\Location\GetLocationByRegulationOrderQuery;
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

        if (!$this->canOrganizationAccessToRegulation->isSatisfiedBy($originalRegulationOrderRecord, $organization)) {
            throw new RegulationCannotBeDuplicated();
        }

        $duplicatedRegulationOrderRecord = $this->duplicateRegulationOrderRecord($organization, $originalRegulationOrder);
        $duplicatedRegulationOrder = $duplicatedRegulationOrderRecord->getRegulationOrder();

        $this->duplicateLocation($originalRegulationOrder, $duplicatedRegulationOrder);

        return $duplicatedRegulationOrderRecord;
    }

    private function duplicateRegulationOrderRecord(
        Organization $organization,
        RegulationOrder $originalRegulationOrder,
    ): RegulationOrderRecord {
        $step1Command = new SaveRegulationOrderCommand();
        $step1Command->organization = $organization;
        $step1Command->identifier = $this->translator->trans('regulation.identifier.copy', [
            '%identifier%' => $originalRegulationOrder->getIdentifier(),
        ]);
        $step1Command->description = $originalRegulationOrder->getDescription();
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
}
