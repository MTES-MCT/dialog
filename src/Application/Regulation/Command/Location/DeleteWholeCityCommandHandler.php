<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Domain\Regulation\Repository\WholeCityRepositoryInterface;

final class DeleteWholeCityCommandHandler
{
    public function __construct(
        private WholeCityRepositoryInterface $wholeCityRepository,
    ) {
    }

    public function __invoke(DeleteWholeCityCommand $command): void
    {
        $this->wholeCityRepository->delete($command->wholeCity);
    }
}
