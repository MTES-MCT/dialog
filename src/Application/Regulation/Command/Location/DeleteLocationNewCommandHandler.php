<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Domain\Regulation\Repository\LocationNewRepositoryInterface;

final class DeleteLocationNewCommandHandler
{
    public function __construct(
        private LocationNewRepositoryInterface $locationNewRepository,
    ) {
    }

    public function __invoke(DeleteLocationNewCommand $command): void
    {
        $this->locationNewRepository->delete($command->locationNew);
    }
}
