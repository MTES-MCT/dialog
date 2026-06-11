<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateRegulationWithMeasureCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private ObjectMapperInterface $objectMapper,
        private MeasureRepositoryInterface $measureRepository,
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

        foreach ($command->measureDtos ?? [] as $measureDto) {
            $measureCommand = SaveMeasureCommand::create($regulationOrder);
            $this->objectMapper->map($measureDto, $measureCommand);

            foreach ($measureCommand->locations as $locationCommand) {
                $locationCommand->organization = $organization;
            }

            $measure = $this->commandBus->handle($measureCommand);
            $regulationOrder->addMeasure($measure);
        }

        if ($command->status === RegulationOrderRecordStatusEnum::PUBLISHED && $regulationOrderRecord->isDraft()) {
            $this->commandBus->handle(new PublishRegulationCommand($regulationOrderRecord));
        }

        return $regulationOrderRecord;
    }
}
