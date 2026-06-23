<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Domain\Regulation\Location\WholeCity;
use App\Domain\Regulation\Location\WholeCityException;
use App\Domain\Regulation\Repository\WholeCityRepositoryInterface;

final class SaveWholeCityCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private WholeCityRepositoryInterface $wholeCityRepository,
    ) {
    }

    public function __invoke(SaveWholeCityCommand $command): WholeCity
    {
        $command->clean();

        if (!$command->wholeCity instanceof WholeCity) {
            $wholeCity = $this->wholeCityRepository->add(
                new WholeCity(
                    uuid: $this->idFactory->make(),
                    location: $command->location,
                    cityCode: $command->cityCode,
                    cityLabel: $command->cityLabel,
                ),
            );
            $command->location->setWholeCity($wholeCity);
            $this->syncExceptions($wholeCity, $command);

            return $wholeCity;
        }

        $command->wholeCity->update(
            cityCode: $command->cityCode,
            cityLabel: $command->cityLabel,
        );
        $this->syncExceptions($command->wholeCity, $command);

        return $command->wholeCity;
    }

    private function syncExceptions(WholeCity $wholeCity, SaveWholeCityCommand $command): void
    {
        // Replace the whole set of exceptions (orphan removal deletes the old ones).
        foreach ($wholeCity->getExceptions() as $existingException) {
            $wholeCity->removeException($existingException);
        }

        foreach ($command->exceptions as $exceptionCommand) {
            $wholeCity->addException(
                new WholeCityException(
                    uuid: $this->idFactory->make(),
                    wholeCity: $wholeCity,
                    roadBanId: $exceptionCommand->roadBanId,
                    roadName: $exceptionCommand->roadName,
                ),
            );
        }
    }
}
