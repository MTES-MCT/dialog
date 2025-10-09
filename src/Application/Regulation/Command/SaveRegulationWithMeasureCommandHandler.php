<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
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

        $measureCommand = SaveMeasureCommand::create($regulationOrderRecord->getRegulationOrder());
        $this->objectMapper->map($command->measureDto, $measureCommand);

        $organization = $command->generalInfo->organization;
        foreach ($measureCommand->locations as $locationCommand) {
            $locationCommand->organization = $organization;
        }

        $measure = $this->commandBus->handle($measureCommand);
        $regulationOrderRecord->getRegulationOrder()->addMeasure($measure);

        $this->commandBus->handle(new PublishRegulationCommand($regulationOrderRecord));

        return $regulationOrderRecord;
    }
}
