<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Domain\Regulation\Repository\RawGeoJSONRepositoryInterface;

final class DeleteRawGeoJSONCommandHandler
{
    public function __construct(
        private RawGeoJSONRepositoryInterface $rawGeoJSONRepository,
    ) {
    }

    public function __invoke(DeleteRawGeoJSONCommand $command): void
    {
        $this->rawGeoJSONRepository->delete($command->rawGeoJSON);
    }
}
