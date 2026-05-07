<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\MapImage\WarmRegulationMapImageCacheCommand;
use App\Domain\Regulation\Exception\MeasureCannotBeDeletedException;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;
use App\Domain\Regulation\Specification\CanDeleteMeasures;

final class DeleteMeasureCommandHandler
{
    public function __construct(
        private MeasureRepositoryInterface $measureRepository,
        private CanDeleteMeasures $canDeleteMeasures,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(DeleteMeasureCommand $command): void
    {
        $regulationOrder = $command->measure->getRegulationOrder();
        $regulationOrderRecord = $regulationOrder->getRegulationOrderRecord();

        if (!$this->canDeleteMeasures->isSatisfiedBy($regulationOrderRecord)) {
            throw new MeasureCannotBeDeletedException();
        }

        $this->measureRepository->delete($command->measure);

        if ($regulationOrderRecord !== null) {
            $this->commandBus->dispatchAsync(new WarmRegulationMapImageCacheCommand($regulationOrderRecord->getUuid()));
        }
    }
}
