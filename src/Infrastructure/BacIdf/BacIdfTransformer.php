<?php

declare(strict_types=1);

namespace App\Infrastructure\BacIdf;

use App\Application\BacIdf\Command\ImportBacIdfRegulationCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Geography\GeometryFormatter;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\LocationAddress;
use App\Domain\User\Organization;

final class BacIdfTransformer
{
    public function __construct(
        private readonly GeometryFormatter $geometryFormatter,
    ) {
    }

    public function transform(array $row, Organization $organization): BacIdfTransformerResult
    {
        $temporality = $row['ARR_DUREE']['ARR_TEMPORALITE'];

        if ($temporality !== 'PERMANENT') {
            return new BacIdfTransformerResult(null, ['temporality is not PERMANENT']);
        }

        $generalInfo = new SaveRegulationGeneralInfoCommand();
        $generalInfo->organization = $organization;
        $generalInfo->identifier = $row['ARR_REF'];
        $generalInfo->category = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        $generalInfo->description = $row['ARR_NOM'];

        $date = $row['ARR_DUREE']['PERIODE_DEBUT']['$date'];

        if (!\is_string($date)) {
            // Probably a $numberLong, which seem to contain inconsistent data (eg dates ranging from 632 to 2040...).
            return new BacIdfTransformerResult(null, [sprintf('PERIOD_DEBUT.$date is not a string: %s', json_encode($date))]);
        }

        $generalInfo->startDate = new \DateTimeImmutable($row['ARR_DUREE']['PERIODE_DEBUT']['$date']);

        $locations = [];

        foreach ($row['REG_CIRCULATION'] as $regCirculation) {
            // TODO
            // $regCirculation['CIRC_VEHICULES']['VEH_POIDS']['']

            // TODO
            // $regCirculation['CIRC_REG']['REG_EXCEPT']

            // TODO
            // $regCirculation['CIRC_REG']['PERIODE_JH']

            foreach ($regCirculation['CIRC_REG']['REG_VOIES'] as $regVoie) {
                if (\count($regVoie['VOIE_GEOJSON']['features']) === 0) {
                    // Probably a road-less POI such as public squares or a roundabout.
                    continue;
                }

                $location = new SaveRegulationLocationCommand();

                $points = $regVoie['VOIE_GEOJSON']['features'][0]['geometry']['coordinates'];
                $start = $points[0];
                $end = $points[\count($points) - 1];

                $location->address = (string) new LocationAddress(
                    postCode: $row['ARR_COMMUNE']['ARR_CODE_POSTAL'],
                    city: $row['ARR_COMMUNE']['ARR_VILLE'],
                    roadName: $regVoie['VOIE_NAME'],
                );
                $location->fromPoint = $this->geometryFormatter->formatPoint($start[1], $start[0]);
                $location->toPoint = $this->geometryFormatter->formatPoint($end[1], $end[0]);
                $location->fromHouseNumber = null;
                $location->toHouseNumber = null;

                $measureCommand = new SaveMeasureCommand();
                $measureCommand->type = MeasureTypeEnum::NO_ENTRY->value;
                $vehicleSet = new SaveVehicleSetCommand();
                $vehicleSet->allVehicles = true;
                $measureCommand->vehicleSet = $vehicleSet;

                // TODO add measure
                $location->measures[] = $measureCommand;

                $locations[] = $location;
            }
        }

        $command = new ImportBacIdfRegulationCommand($generalInfo, $locations);

        return new BacIdfTransformerResult($command, []);
    }
}
