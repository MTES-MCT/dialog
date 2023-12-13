<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;
use App\Application\GeocoderInterface;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\EudonetParis\EudonetParisLocationItem;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\User\Organization;

final class EudonetParisTransformer
{
    public function __construct(
        private GeocoderInterface $geocoder,
    ) {
    }

    public function transform(array $row, Organization $organization): EudonetParisTransformerResult
    {
        $errors = [];
        $loc = ['regulation_identifier' => $row['fields'][EudonetParisExtractor::ARRETE_ID]];

        $generalInfoCommand = $this->buildGeneralInfoCommand($row, $organization);

        $locationItems = [];
        $errors = [];

        if (\count($row['measures']) === 0) {
            $errors[] = ['loc' => $loc, 'impact' => 'skip_regulation', 'reason' => 'no_measures_found'];

            return new EudonetParisTransformerResult(null, $errors);
        }

        foreach ($row['measures'] as $measureRow) {
            [$measureCommand, $error] = $this->buildMeasureCommand($measureRow);

            if (empty($measureCommand)) {
                $errors[] = ['loc' => [...$loc, ...$error['loc']], ...array_diff_key($error, ['loc' => '']), 'impact' => 'skip_measure'];
                continue;
            }

            $vehicleSet = new SaveVehicleSetCommand();
            $vehicleSet->allVehicles = true;
            $measureCommand->vehicleSet = $vehicleSet;

            foreach ($measureRow['locations'] as $locationRow) {
                [$locationItem, $error] = $this->buildLocationItem($locationRow, $measureCommand);

                if (empty($locationItem)) {
                    $errors[] = ['loc' => [...$loc, ...$error['loc']], ...array_diff_key($error, ['loc' => '']), 'impact' => 'skip_location'];
                    continue;
                }

                $locationItems[] = $locationItem;
            }
        }

        if (\count($locationItems) === 0) {
            $errors[] = ['loc' => $loc, 'impact' => 'skip_regulation', 'reason' => 'no_locations_gathered'];

            return new EudonetParisTransformerResult(null, $errors);
        }

        $command = new ImportEudonetParisRegulationCommand(
            $generalInfoCommand,
            $locationItems,
        );

        return new EudonetParisTransformerResult($command, $errors);
    }

    private function buildGeneralInfoCommand(array $row, Organization $organization): SaveRegulationGeneralInfoCommand
    {
        $command = new SaveRegulationGeneralInfoCommand();
        $command->identifier = $row['fields'][EudonetParisExtractor::ARRETE_ID];

        $type = $row['fields'][EudonetParisExtractor::ARRETE_TYPE];

        $command->category = RegulationOrderCategoryEnum::OTHER->value;
        $command->otherCategoryText = $type;

        // Adhere to character limit
        $command->description = mb_substr($row['fields'][EudonetParisExtractor::ARRETE_COMPLEMENT_DE_TITRE], 0, 255);

        $command->organization = $organization;

        $command->startDate = \DateTimeImmutable::createFromFormat(
            'Y/m/d H:i:s',
            $row['fields'][EudonetParisExtractor::ARRETE_DATE_DEBUT],
            new \DateTimeZone('Europe/Paris'),
        );

        $command->endDate = \DateTimeImmutable::createFromFormat(
            'Y/m/d H:i:s',
            $row['fields'][EudonetParisExtractor::ARRETE_DATE_FIN],
            new \DateTimeZone('Europe/Paris'),
        );

        return $command;
    }

    private function buildMeasureCommand(array $row): array
    {
        $loc = ['measure_id' => $row['fields'][EudonetParisExtractor::MESURE_ID]];

        $name = $row['fields'][EudonetParisExtractor::MESURE_NOM];

        if (strtolower($name) !== 'circulation interdite') {
            $error = ['loc' => [...$loc, 'fieldname' => 'NOM'], 'reason' => 'value_not_in_enum', 'value' => $name, 'enum' => ['circulation interdite']];

            return [null, $error];
        }

        $command = new SaveMeasureCommand();
        $command->type = MeasureTypeEnum::NO_ENTRY->value;

        return [$command, null];
    }

    private function buildLocationItem(array $row, SaveMeasureCommand $measureCommand): array
    {
        $loc = ['location_id' => $row['fields'][EudonetParisExtractor::LOCALISATION_ID]];

        $locationItem = new EudonetParisLocationItem();

        $porteSur = $row['fields'][EudonetParisExtractor::LOCALISATION_PORTE_SUR];
        $libelleVoie = $row['fields'][EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE];
        $libelleVoieDebut = $row['fields'][EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_DEBUT];
        $libelleVoieFin = $row['fields'][EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_FIN];
        $numAdresseDebut = $row['fields'][EudonetParisExtractor::LOCALISATION_N_ADRESSE_DEBUT];
        $numAdresseFin = $row['fields'][EudonetParisExtractor::LOCALISATION_N_ADRESSE_FIN];

        $fromHouseNumber = null;
        $toHouseNumber = null;
        $fromRoadName = null;
        $toRoadName = null;

        $hasStart = $numAdresseDebut || $libelleVoieDebut;
        $hasEnd = $numAdresseFin || $libelleVoieFin;

        if ($porteSur === 'La totalité de la voie' && $libelleVoie) {
            $roadName = $libelleVoie;
        } elseif (\in_array($porteSur, ['Une section', 'Une zone', 'Un axe']) && $libelleVoie && $hasStart && $hasEnd) {
            $roadName = $libelleVoie;
            $fromHouseNumber = $numAdresseDebut;
            $toHouseNumber = $numAdresseFin;
            $fromRoadName = $libelleVoieDebut;
            $toRoadName = $libelleVoieFin;
        } else {
            $error = [
                'loc' => $loc,
                'reason' => 'unsupported_location_fieldset',
                'location_raw' => json_encode($row),
            ];

            return [null, $error];
        }

        $locationItem->roadName = $roadName;
        $locationItem->fromHouseNumber = $fromHouseNumber;
        $locationItem->toHouseNumber = $toHouseNumber;
        $locationItem->geometry = $this->makeLocationGeometry($locationItem->roadName, $fromHouseNumber, $fromRoadName, $toHouseNumber, $toRoadName);

        $locationItem->measures[] = $measureCommand;

        return [$locationItem, null];
    }

    private function makeLocationGeometry(
        string $roadName,
        ?string $fromHouseNumber,
        ?string $fromRoadName,
        ?string $toHouseNumber,
        ?string $toRoadName,
    ): ?string {
        $hasBothEnds = (
            ($fromHouseNumber || $fromRoadName)
            && ($toHouseNumber || $toRoadName)
        );

        if (!$hasBothEnds) {
            return null;
        }

        if ($fromHouseNumber) {
            $fromAddress = sprintf('%s %s', $fromHouseNumber, $roadName);
            $fromPoint = $this->geocoder->computeCoordinates($fromAddress, ImportEudonetParisRegulationCommand::CITY_CODE);
        } else {
            $fromPoint = $this->geocoder->computeJunctionCoordinates($roadName, $fromRoadName, ImportEudonetParisRegulationCommand::CITY_CODE);
        }

        if ($toHouseNumber) {
            $toAddress = sprintf('%s %s', $toHouseNumber, $roadName);
            $toPoint = $this->geocoder->computeCoordinates($toAddress, ImportEudonetParisRegulationCommand::CITY_CODE);
        } else {
            $toPoint = $this->geocoder->computeJunctionCoordinates($roadName, $toRoadName, ImportEudonetParisRegulationCommand::CITY_CODE);
        }

        return GeoJSON::toLineString([$fromPoint, $toPoint]);
    }
}
