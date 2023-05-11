<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Domain\Regulation\Repository\MeasureRepositoryInterface;

final class DeleteMeasureCommandHandler
{
    public function __construct(
        private MeasureRepositoryInterface $measureRepository,
    ) {
    }

    public function __invoke(DeleteMeasureCommand $command): void
    {
        $this->measureRepository->delete($command->measure);
    }
}
