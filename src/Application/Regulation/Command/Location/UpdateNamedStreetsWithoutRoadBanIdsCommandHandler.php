<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandBusInterface;
use App\Application\Exception\GeocodingFailureException;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Repository\NamedStreetRepositoryInterface;

final class UpdateNamedStreetsWithoutRoadBanIdsCommandHandler
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly NamedStreetRepositoryInterface $namedStreetRepository,
        private readonly RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    public function __invoke(UpdateNamedStreetsWithoutRoadBanIdsCommand $command): mixed
    {
        $namedStreets = $this->namedStreetRepository->findAllWithoutRoadBanIds();

        $numNamedStreets = \count($namedStreets);
        $updatedUuids = [];
        $exceptions = [];

        foreach ($namedStreets as $namedStreet) {
            $namedStreetCommand = new SaveNamedStreetCommand($namedStreet);

            if (!$namedStreetCommand->roadName) {
                // Some historical data contains roadName = NULL, but this is now prohibited by validation
                // Skip updating them since there's no road to compute, this also avoids validation errors.
                continue;
            }

            try {
                $changed = false;

                if (!$namedStreetCommand->roadBanId) {
                    $namedStreetCommand->roadBanId = $this->roadGeocoder->computeRoadBanId($namedStreetCommand->roadName, $namedStreetCommand->cityCode);
                    $changed = true;
                }

                if ($namedStreetCommand->fromRoadName && !$namedStreetCommand->fromRoadBanId) {
                    $namedStreetCommand->fromRoadBanId = $this->roadGeocoder->computeRoadBanId($namedStreetCommand->fromRoadName, $namedStreetCommand->cityCode);
                    $changed = true;
                }

                if ($namedStreetCommand->toRoadName && !$namedStreetCommand->toRoadBanId) {
                    $namedStreetCommand->toRoadBanId = $this->roadGeocoder->computeRoadBanId($namedStreetCommand->toRoadName, $namedStreetCommand->cityCode);
                    $changed = true;
                }

                if (!$changed) {
                    // Speed up: don't execute update command if no changes
                    continue;
                }

                $this->commandBus->handle($namedStreetCommand);
                $updatedUuids[] = $namedStreet->getUuid();
            } catch (GeocodingFailureException $exc) {
                $exceptions[$namedStreet->getUuid()] = $exc;
            }
        }

        return new UpdateNamedStreetsWithoutRoadBanIdsCommandResult($numNamedStreets, $updatedUuids, $exceptions);
    }
}
