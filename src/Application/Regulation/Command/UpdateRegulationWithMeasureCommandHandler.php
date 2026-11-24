<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\ApiMeasuresSaver;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;

final class UpdateRegulationWithMeasureCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MeasureRepositoryInterface $measureRepository,
        private ApiMeasuresSaver $apiMeasuresSaver,
    ) {
    }

    public function __invoke(UpdateRegulationWithMeasureCommand $command): RegulationOrderRecord
    {
        /** @var RegulationOrderRecord $regulationOrderRecord */
        $regulationOrderRecord = $this->commandBus->handle($command->generalInfo);
        $organization = $command->generalInfo->organization;
        $regulationOrder = $regulationOrderRecord->getRegulationOrder();

        foreach (iterator_to_array($regulationOrder->getMeasures()) as $measure) {
            $regulationOrder->removeMeasure($measure);
            $this->measureRepository->delete($measure);
        }

        $this->apiMeasuresSaver->save($regulationOrderRecord, $organization, $command->measureDtos ?? [], $command->rawMeasures);

        if ($command->status === RegulationOrderRecordStatusEnum::PUBLISHED && $regulationOrderRecord->isDraft()) {
            $this->commandBus->handle(new PublishRegulationCommand($regulationOrderRecord));
        }

        return $regulationOrderRecord;
    }
}
