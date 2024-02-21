<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Domain\Regulation\Repository\LocationRepositoryInterface;

final class DeleteLocationCommandHandler
{
    public function __construct(
        private LocationRepositoryInterface $locationNewRepository,
    ) {
    }

    public function __invoke(DeleteLocationCommand $command): void
    {
        $this->locationNewRepository->delete($command->locationNew);
    }
}
