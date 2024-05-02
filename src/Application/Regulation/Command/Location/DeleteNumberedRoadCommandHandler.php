<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Domain\Regulation\Repository\NumberedRoadRepositoryInterface;

final class DeleteNumberedRoadCommandHandler
{
    public function __construct(
        private NumberedRoadRepositoryInterface $numberedRoadRepository,
    ) {
    }

    public function __invoke(DeleteNumberedRoadCommand $command): void
    {
        $this->numberedRoadRepository->delete($command->numberedRoad);
    }
}
