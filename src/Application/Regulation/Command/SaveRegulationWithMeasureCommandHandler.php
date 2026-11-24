<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\ApiMeasuresSaver;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrderRecord;

final class SaveRegulationWithMeasureCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private ApiMeasuresSaver $apiMeasuresSaver,
    ) {
    }

    public function __invoke(SaveRegulationWithMeasureCommand $command): RegulationOrderRecord
    {
        $regulationOrderRecord = $this->commandBus->handle($command->generalInfo);
        $organization = $command->generalInfo->organization;

        $this->apiMeasuresSaver->save($regulationOrderRecord, $organization, $command->measureDtos ?? [], $command->rawMeasures);

        if ($command->status === RegulationOrderRecordStatusEnum::PUBLISHED) {
            $this->commandBus->handle(new PublishRegulationCommand($regulationOrderRecord));
        }

        return $regulationOrderRecord;
    }
}
