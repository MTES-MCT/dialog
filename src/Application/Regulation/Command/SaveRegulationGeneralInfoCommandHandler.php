<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\IdFactoryInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\User\Exception\OrganizationAlreadyHasRegulationOrderWithThisIdentifierException;
use App\Domain\User\Specification\DoesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier;

final class SaveRegulationGeneralInfoCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private DoesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier $doesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier,
        private \DateTimeInterface $now,
    ) {
    }

    public function __invoke(SaveRegulationGeneralInfoCommand $command): RegulationOrderRecord
    {
        // Checking the unicity of an regulation order identifier in an organization
        $regulationOrder = $command->regulationOrderRecord?->getRegulationOrder();
        $hasIdentifierChanged = $regulationOrder?->getIdentifier() !== $command->identifier;
        $hasOrganizationChanged = $command->regulationOrderRecord?->getOrganization() !== $command->organization;

        if ($hasIdentifierChanged || $hasOrganizationChanged) {
            if ($this->doesOrganizationAlreadyHaveRegulationOrderWithThisIdentifier
                ->isSatisfiedBy($command->identifier, $command->organization)) {
                throw new OrganizationAlreadyHasRegulationOrderWithThisIdentifierException();
            }
        }

        // If submitting the form the first time, we create the regulationOrder and regulationOrderRecord
        if (!$command->regulationOrderRecord instanceof RegulationOrderRecord) {
            $regulationOrder = $this->regulationOrderRepository->add(
                new RegulationOrder(
                    uuid: $this->idFactory->make(),
                    identifier: $command->identifier,
                    category: $command->category,
                    description: $command->description,
                    startDate: $command->startDate,
                    endDate: $command->endDate,
                ),
            );

            $regulationOrderRecord = $this->regulationOrderRecordRepository->add(
                new RegulationOrderRecord(
                    uuid: $this->idFactory->make(),
                    status: RegulationOrderRecordStatusEnum::DRAFT,
                    regulationOrder: $regulationOrder,
                    createdAt: $this->now,
                    organization: $command->organization,
                ),
            );

            return $regulationOrderRecord;
        }

        $command->regulationOrderRecord->updateOrganization($command->organization);
        $command->regulationOrderRecord->getRegulationOrder()->update(
            identifier: $command->identifier,
            category: $command->category,
            description: $command->description,
            startDate: $command->startDate,
            endDate: $command->endDate,
        );

        return $command->regulationOrderRecord;
    }
}
