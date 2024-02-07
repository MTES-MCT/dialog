<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandBusInterface;
use App\Application\GeocoderInterface;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\Location\SaveLocationNewCommand;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Exception\RoadNumberNotFoundException;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use Http\Client\Exception\HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SaveRegulationLocationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private CommandBusInterface $commandBus,
        private LocationRepositoryInterface $locationRepository,
        private GeocoderInterface $geocoder,
        private RoadGeocoderInterface $roadGeocoder,
        private HttpClientInterface $ignWfsClient,
        private string $ignWfsUrl,
    ) {
    }

    public function __invoke(SaveRegulationLocationCommand $command): Location
    {
        $regulationOrder = $command->regulationOrderRecord->getRegulationOrder();
        // resets values to null when changing location type
        $command->clean();
        // Create location if needed
        if (!$command->location instanceof Location) {
            // NOTE: move this to LocationNew handlers when Location entity is removed.
            $geometry = empty($command->geometry) ? $this->computeGeometry($command) : $command->geometry;

            $location = $this->locationRepository->add(
                new Location(
                    uuid: $this->idFactory->make(),
                    regulationOrder: $regulationOrder,
                    roadType: $command->roadType,
                    administrator: $command->administrator,
                    roadNumber: $command->roadNumber,
                    cityCode: $command->cityCode,
                    cityLabel: $command->cityLabel,
                    roadName: $command->roadName,
                    fromHouseNumber: $command->fromHouseNumber,
                    toHouseNumber: $command->toHouseNumber,
                    geometry: $geometry,
                ),
            );

            foreach ($command->measures as $measureCommand) {
                $measureCommand->location = $location;
                $measureCommand->locationsNew = [SaveLocationNewCommand::fromLocation($location)];
                $measure = $this->commandBus->handle($measureCommand);
                $location->addMeasure($measure);
            }

            $regulationOrder->addLocation($location);
            $gestionnaire = $command->administrator;

            if ($command->roadType === 'departmentalRoad') {
                $type_de_route = 'Départementale';
                $roadNumberIsValid = $this->roadNumberIsValid($gestionnaire, $type_de_route);

                return $roadNumberIsValid;
            }

            return $location;
        }

        $geometry = $this->shouldRecomputeGeometry($command)
            ? $this->computeGeometry($command)
            : $command->location->getGeometry();

        $command->location->update(
            roadType: $command->roadType,
            administrator: $command->administrator,
            roadNumber: $command->roadNumber,
            cityCode: $command->cityCode,
            cityLabel: $command->cityLabel,
            roadName: $command->roadName,
            fromHouseNumber: $command->fromHouseNumber,
            toHouseNumber: $command->toHouseNumber,
            geometry: $geometry,
        );

        $measuresStillPresentUuids = [];

        // Measures provided with the command get created or updated...
        foreach ($command->measures as $measureCommand) {
            if ($measureCommand->measure) {
                $measuresStillPresentUuids[] = $measureCommand->measure->getUuid();
            }

            $measureCommand->location = $command->location;

            if ($measureCommand->locationsNew) {
                $measureCommand->locationsNew[0] = SaveLocationNewCommand::fromLocation(
                    $command->location,
                    $measureCommand->locationsNew[0]->locationNew,
                );
            } else {
                $measureCommand->locationsNew = [SaveLocationNewCommand::fromLocation($command->location)];
            }

            $this->commandBus->handle($measureCommand);
        }

        // Measures that weren't present in the command get deleted.
        foreach ($command->location->getMeasures() as $measure) {
            if (!\in_array($measure->getUuid(), $measuresStillPresentUuids)) {
                $command->location->removeMeasure($measure);
                $this->commandBus->handle(new DeleteMeasureCommand($measure));
            }
        }

        return $command->location;
    }

    private function computeGeometry(SaveRegulationLocationCommand $command): ?string
    {
        if ($command->fromHouseNumber && $command->toHouseNumber) {
            $fromAddress = sprintf('%s %s', $command->fromHouseNumber, $command->roadName);
            $toAddress = sprintf('%s %s', $command->toHouseNumber, $command->roadName);

            $fromCoords = $this->geocoder->computeCoordinates($fromAddress, $command->cityCode);
            $toCoords = $this->geocoder->computeCoordinates($toAddress, $command->cityCode);

            return GeoJSON::toLineString([$fromCoords, $toCoords]);
        }

        $roadName = $command->roadName;
        $cityCode = $command->cityCode;

        if (!$command->fromHouseNumber && !$command->toHouseNumber && $roadName) {
            return $this->roadGeocoder->computeRoadLine($roadName, $cityCode);
        }

        return null;
    }

    private function shouldRecomputeGeometry(SaveRegulationLocationCommand $command): bool
    {
        return $command->cityCode !== $command->location->getCityCode()
            || $command->roadName !== $command->location->getRoadName()
            || ($command->fromHouseNumber !== $command->location->getFromHouseNumber())
            || ($command->toHouseNumber !== $command->location->getToHouseNumber());
    }

    public function roadNumberIsValid(string $gestionnaire, string $type_de_route): void
    {
        $query = [
            'SERVICE' => 'WFS',
            'REQUEST' => 'GetFeature',
            'VERSION' => '2.0.0',
            'OUTPUTFORMAT' => 'application/json',
            'TYPENAME' => 'BDTOPO_V3:route_numerotee_ou_nommee',
            'cql_filter' => sprintf("gestionnaire='%s' AND type_de_route='%s'", $gestionnaire, $type_de_route),
            'PropertyName' => 'numero',
        ];

        $response = $this->ignWfsClient->request('GET', $this->ignWfsUrl, [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => $query,
        ]);
        try {
            $body = $response->getContent(throw: true);
        } catch (HttpException $exc) {
            $message = sprintf('invalid response: %s', $exc->getMessage());
            throw new RoadNumberNotFoundException($message);
        }
    }
}
