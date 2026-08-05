<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Domain\Regulation\Repository\ZoneRepositoryInterface;

final class DeleteZoneCommandHandler
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
    ) {
    }

    public function __invoke(DeleteZoneCommand $command): void
    {
        $this->zoneRepository->delete($command->zone);
    }
}
