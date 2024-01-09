<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Domain\Regulation\Exception\LocationCannotBeDeletedException;
use App\Domain\Regulation\Exception\LocationDoesntBelongsToRegulationOrderException;
use App\Domain\Regulation\Exception\LocationNotFoundException;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\Regulation\Specification\CanDeleteLocations;

// todo : refacto
final class DeleteRegulationLocationCommandHandler
{
    public function __construct(
        private LocationRepositoryInterface $locationRepository,
        private CanDeleteLocations $canDeleteLocations,
    ) {
    }

    public function __invoke(DeleteRegulationLocationCommand $command): void
    {
        $regulationOrderRecord = $command->regulationOrderRecord;

        if (!$this->canDeleteLocations->isSatisfiedBy($regulationOrderRecord)) {
            throw new LocationCannotBeDeletedException();
        }

        $regulationOrder = $regulationOrderRecord->getRegulationOrder();
        $location = $this->locationRepository->findOneByUuid($command->uuid);

        if (!$location instanceof Location) {
            throw new LocationNotFoundException();
        }

        if ($location->getRegulationOrder() !== $regulationOrder) {
            throw new LocationDoesntBelongsToRegulationOrderException();
        }

        $this->locationRepository->delete($location);
    }
}
