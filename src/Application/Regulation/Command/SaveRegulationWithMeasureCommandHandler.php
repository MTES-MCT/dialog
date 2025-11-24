<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrderRecord;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class SaveRegulationWithMeasureCommandHandler
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private ObjectMapperInterface $objectMapper,
    ) {
    }

    public function __invoke(SaveRegulationWithMeasureCommand $command): RegulationOrderRecord
    {
        $regulationOrderRecord = $this->commandBus->handle($command->generalInfo);
        $organization = $command->generalInfo->organization;

        foreach ($command->measureDtos ?? [] as $measureDto) {
            $measureCommand = SaveMeasureCommand::create($regulationOrderRecord->getRegulationOrder());
            $this->objectMapper->map($measureDto, $measureCommand);

            foreach ($measureCommand->locations as $locationCommand) {
                $locationCommand->organization = $organization;
            }

            $measure = $this->commandBus->handle($measureCommand);
            $regulationOrderRecord->getRegulationOrder()->addMeasure($measure);
        }

        if ($command->status === RegulationOrderRecordStatusEnum::PUBLISHED) {
            $this->commandBus->handle(new PublishRegulationCommand($regulationOrderRecord));
        }

        return $regulationOrderRecord;
    }
}
