<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Domain\Regulation\Exception\MeasureCannotBeDeletedException;
use App\Domain\Regulation\Repository\MeasureRepositoryInterface;
use App\Domain\Regulation\Specification\CanDeleteMeasures;

final class DeleteMeasureCommandHandler
{
    public function __construct(
        private MeasureRepositoryInterface $measureRepository,
        private CanDeleteMeasures $canDeleteMeasures,
    ) {
    }

    public function __invoke(DeleteMeasureCommand $command): void
    {
        $regulationOrder = $command->measure->getRegulationOrder();

        if (!$this->canDeleteMeasures->isSatisfiedBy($regulationOrder->getRegulationOrderRecord())) {
            throw new MeasureCannotBeDeletedException();
        }

        $this->measureRepository->delete($command->measure);
    }
}
