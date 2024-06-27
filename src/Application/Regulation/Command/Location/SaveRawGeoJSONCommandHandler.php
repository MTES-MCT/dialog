<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\IdFactoryInterface;
use App\Domain\Regulation\Location\RawGeoJSON;
use App\Domain\Regulation\Repository\RawGeoJSONRepositoryInterface;

final class SaveRawGeoJSONCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private RawGeoJSONRepositoryInterface $rawGeoJSONRepository,
    ) {
    }

    public function __invoke(SaveRawGeoJSONCommand $command): RawGeoJSON
    {
        if (!$command->rawGeoJSON instanceof RawGeoJSON) {
            $rawGeoJSON = $this->rawGeoJSONRepository->add(
                new RawGeoJSON(
                    uuid: $this->idFactory->make(),
                    location: $command->location,
                    label: $command->label,
                ),
            );

            $command->location->setRawGeoJSON($rawGeoJSON);

            return $rawGeoJSON;
        }

        $command->rawGeoJSON->update(
            label: $command->label,
        );

        return $command->rawGeoJSON;
    }
}
