<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\IdFactoryInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\RegulationOrderTemplate\GetRegulationOrderTemplateQuery;
use App\Domain\Regulation\Enum\ActionTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;

final class SaveRegulationGeneralInfoCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private RegulationOrderRepositoryInterface $regulationOrderRepository,
        private RegulationOrderRecordRepositoryInterface $regulationOrderRecordRepository,
        private \DateTimeInterface $now,
        private QueryBusInterface $queryBus,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(SaveRegulationGeneralInfoCommand $command): RegulationOrderRecord
    {
        $command->cleanOtherCategoryText();

        $regulationOrderTemplate = $command->regulationOrderTemplateUuid
            ? $this->queryBus->handle(new GetRegulationOrderTemplateQuery($command->regulationOrderTemplateUuid))
            : null;

        // If submitting the form the first time, we create the regulationOrder and regulationOrderRecord
        if (!$command->regulationOrderRecord instanceof RegulationOrderRecord) {
            $regulationOrder = $this->regulationOrderRepository->add(
                new RegulationOrder(
                    uuid: $this->idFactory->make(),
                    identifier: $command->identifier,
                    category: $command->category,
                    subject: $command->subject,
                    title: $command->title,
                    otherCategoryText: $command->otherCategoryText,
                    regulationOrderTemplate: $regulationOrderTemplate,
                ),
            );
            $regulationOrderRecord = $this->regulationOrderRecordRepository->add(
                new RegulationOrderRecord(
                    uuid: $this->idFactory->make(),
                    source: $command->source,
                    status: RegulationOrderRecordStatusEnum::DRAFT->value,
                    regulationOrder: $regulationOrder,
                    createdAt: $this->now,
                    organization: $command->organization,
                ),
            );

            $this->commandBus->handle(new CreateRegulationOrderHistoryCommand($regulationOrder, ActionTypeEnum::CREATE->value));

            return $regulationOrderRecord;
        }

        $command->regulationOrderRecord->updateOrganization($command->organization);
        $regulationOrder = $command->regulationOrderRecord->getRegulationOrder();

        $regulationOrder->update(
            identifier: $command->identifier,
            category: $command->category,
            subject: $command->subject,
            title: $command->title,
            otherCategoryText: $command->otherCategoryText,
            regulationOrderTemplate: $regulationOrderTemplate,
        );

        $this->commandBus->handle(new CreateRegulationOrderHistoryCommand($regulationOrder, ActionTypeEnum::UPDATE->value));

        return $command->regulationOrderRecord;
    }
}
