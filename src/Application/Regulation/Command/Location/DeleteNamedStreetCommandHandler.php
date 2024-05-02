<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Domain\Regulation\Repository\NamedStreetRepositoryInterface;

final class DeleteNamedStreetCommandHandler
{
    public function __construct(
        private NamedStreetRepositoryInterface $namedstreetRepository,
    ) {
    }

    public function __invoke(DeleteNamedStreetCommand $command): void
    {
        $this->namedstreetRepository->delete($command->namedStreet);
    }
}
