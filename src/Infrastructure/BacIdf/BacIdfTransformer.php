<?php

declare(strict_types=1);

namespace App\Infrastructure\BacIdf;

use App\Application\BacIdf\Command\ImportBacIdfRegulationCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\User\Organization;

final class BacIdfTransformer
{
    public function transform(array $row, Organization $organization): BacIdfTransformerResult
    {
        $temporality = $row['ARR_DUREE']['ARR_TEMPORALITE'];

        if ($temporality !== 'PERMANENT') {
            return new BacIdfTransformerResult(null, [
                [
                    'reason' => 'not permanent',
                    'ARR_TEMPORALITE' => $temporality,
                    'ARR_REF' => $row['ARR_REF'],
                ],
            ]);
        }

        $generalInfo = new SaveRegulationGeneralInfoCommand();
        $generalInfo->organization = $organization;
        $generalInfo->identifier = $row['ARR_REF'];
        $generalInfo->category = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        $generalInfo->description = $row['ARR_NOM'];

        $date = $row['ARR_DUREE']['PERIODE_DEBUT']['$date'];

        if (!\is_string($date)) {
            // Probably a $numberLong, which seem to contain inconsistent data (eg dates ranging from 632 to 2040...).
            return new BacIdfTransformerResult(null, [
                [
                    'reason' => 'PERIODE_DEBUT.$date is not a string',
                    'date' => json_encode($date),
                ],
            ]);
        }

        $generalInfo->startDate = new \DateTimeImmutable($date);

        $locations = [];

        foreach ($row['REG_CIRCULATION'] as $regCirculation) {
            if (empty($regCirculation['CIRC_REG'])) {
                continue;
            }

            $circReg = $regCirculation['CIRC_REG'];

            if (empty($circReg['REG_RESTRICTION']) || $circReg['REG_RESTRICTION'] != true) {
                continue;
            }

            $measureCommand = new SaveMeasureCommand();
            $measureCommand->type = MeasureTypeEnum::NO_ENTRY->value;
            $measureCommand->vehicleSet = $this->parseVehicleSetCommand($regCirculation);

            // TODO periods
            // $regCirculation['CIRC_REG']['PERIODE_JH']

            foreach ($circReg['REG_VOIES'] as $regVoie) {
                if (\count($regVoie['VOIE_GEOJSON']['features']) === 0) {
                    // Probably a road-less POI such as public squares or a roundabout.
                    continue;
                }

                $location = new SaveRegulationLocationCommand();

                $geometries = [];

                foreach ($regVoie['VOIE_GEOJSON']['features'] as $feature) {
                    $geometries[] = $feature['geometry'];
                }

                $geometry = [
                    'type' => 'GeometryCollection',
                    'geometries' => $geometries,
                ];

                $location->cityCode = $row['ARR_COMMUNE']['ARR_INSEE'];
                $location->cityLabel = sprintf('%s (%s)', $row['ARR_COMMUNE']['ARR_VILLE'], $row['ARR_COMMUNE']['ARR_CODE_POSTAL']);
                $location->roadName = $regVoie['VOIE_NAME'];
                $location->geometry = json_encode($geometry, JSON_THROW_ON_ERROR);
                $location->fromHouseNumber = null;
                $location->toHouseNumber = null;

                // TODO add measure
                $location->measures[] = $measureCommand;

                $locations[] = $location;
            }
        }

        if (\count($locations) === 0) {
            return new BacIdfTransformerResult(null, [['reason' => 'no_locations_gathered']]);
        }

        $command = new ImportBacIdfRegulationCommand($generalInfo, $locations);

        return new BacIdfTransformerResult($command, []);
    }

    private function parseVehicleSetCommand(array $regCirculation): SaveVehicleSetCommand
    {
        $vehicleSetCommand = new SaveVehicleSetCommand();

        $allVehicles = true;

        if (isset($regCirculation['CIRC_VEHICULES'])) {
            $vehicules = $regCirculation['CIRC_VEHICULES'];

            // NOTE: Most of the time, BAC-IDF think in terms of "which vehicles are forbidden".
            // But DiaLog thinks in terms of "what are the maximum allowed characteristics".
            // => Most of the timeheir 'min' become a 'max' for us.
            // Some decrees in the data use MAX values, but they are always a mistake.

            if (isset($vehicules['VEH_POIDS'])) {
                $vehPoids = $vehicules['VEH_POIDS'];

                if (isset($vehPoids['VEH_PTAC_MIN'])) {
                    $vehicleSetCommand->heavyweightMaxWeight = $vehPoids['VEH_PTAC_MIN'];
                    $allVehicles = false;
                }
                if (isset($vehPoids['VEH_PTAC_MAX'])) {
                    $vehicleSetCommand->heavyweightMaxWeight = $vehPoids['VEH_PTAC_MAX'];
                    $allVehicles = false;
                }
            }

            if (isset($vehicules['VEH_DIMENSION'])) {
                $dimensions = $vehicules['VEH_DIMENSION'];

                if (isset($dimensions['VEH_LARG_MIN'])) {
                    $vehicleSetCommand->heavyweightMaxWidth = $dimensions['VEH_LARG_MIN'];
                    $allVehicles = false;
                }
                if (isset($dimensions['VEH_LARG_MAX'])) {
                    $vehicleSetCommand->heavyweightMaxWidth = $dimensions['VEH_LARG_MAX'];
                    $allVehicles = false;
                }

                if (isset($dimensions['VEH_LONG_MIN'])) {
                    $vehicleSetCommand->heavyweightMaxLength = $dimensions['VEH_LONG_MIN'];
                    $allVehicles = false;
                }
                if (isset($dimensions['VEH_LONG_MAX'])) {
                    $vehicleSetCommand->heavyweightMaxLength = $dimensions['VEH_LONG_MAX'];
                    $allVehicles = false;
                }

                if (isset($dimensions['VEH_HAUT_MIN'])) {
                    $vehicleSetCommand->heavyweightMaxHeight = $dimensions['VEH_HAUT_MIN'];
                    $allVehicles = false;
                }
                if (isset($dimensions['VEH_HAUT_MAX'])) {
                    $vehicleSetCommand->heavyweightMaxHeight = $dimensions['VEH_HAUT_MAX'];
                    $allVehicles = false;
                }
            }
        }

        $vehicleSetCommand->allVehicles = $allVehicles;

        if (!$allVehicles) {
            // TODO: restrictedTypes is required to save the other fields
            $vehicleSetCommand->restrictedTypes = [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value];
            if (!$vehicleSetCommand->heavyweightMaxWeight) {
                $vehicleSetCommand->heavyweightMaxWeight = 3.5;
            }
        }

        // TODO
        // $regCirculation['CIRC_REG']['REG_EXCEPT']

        return $vehicleSetCommand;
    }
}
