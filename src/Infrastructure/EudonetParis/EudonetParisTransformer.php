<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;
use App\Application\Exception\GeocodingFailureException;
use App\Application\GeocoderInterface;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Geography\GeoJSON;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\User\Organization;

final class EudonetParisTransformer
{
    private const ARRONDISSEMENT_REGEX = '/^(?<arrondissement>\d+)(er|e|ème|eme)\s+arrondissement$/i';
    private const CITY_CODE_TEMPLATE = '751%s';
    private const CITY_LABEL = 'Paris';

    public function __construct(
        private GeocoderInterface $geocoder,
    ) {
    }

    public function transform(array $row, Organization $organization): EudonetParisTransformerResult
    {
        $errors = [];
        $loc = ['regulation_identifier' => $row['fields'][EudonetParisExtractor::ARRETE_ID]];

        if (\count($row['measures']) === 0) {
            $errors[] = ['loc' => $loc, 'impact' => 'skip_regulation', 'reason' => 'no_measures_found'];

            return new EudonetParisTransformerResult(null, $errors);
        }

        [$generalInfoCommand, $error] = $this->buildGeneralInfoCommand($row, $organization);

        if ($error) {
            $errors[] = ['loc' => [...$loc, ...$error['loc']], 'impact' => 'skip_regulation', ...$error];

            return new EudonetParisTransformerResult(null, $errors);
        }

        $locationCommands = [];

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
                [$locationCommand, $error] = $this->buildLocationCommand($locationRow, $measureCommand);

                if (empty($locationCommand)) {
                    $errors[] = ['loc' => [...$loc, ...$error['loc']], ...array_diff_key($error, ['loc' => '']), 'impact' => 'skip_location'];
                    continue;
                }

                $locationCommands[] = $locationCommand;
            }
        }

        if (\count($locationCommands) === 0) {
            $errors[] = ['loc' => $loc, 'impact' => 'skip_regulation', 'reason' => 'no_locations_gathered'];

            return new EudonetParisTransformerResult(null, $errors);
        }

        $command = new ImportEudonetParisRegulationCommand(
            $generalInfoCommand,
            $locationCommands,
        );

        return new EudonetParisTransformerResult($command, $errors);
    }

    private function parseDate(string $value): \DateTimeInterface|null
    {
        $possibleEudonetFormats = ['Y/m/d H:i:s', 'Y/m/d', 'd/m/Y'];

        foreach ($possibleEudonetFormats as $format) {
            if ($date = \DateTimeImmutable::createFromFormat($format, $value, new \DateTimeZone('Europe/Paris'))) {
                return $date;
            }
        }

        return null;
    }

    private function buildGeneralInfoCommand(array $row, Organization $organization): array
    {
        $command = new SaveRegulationGeneralInfoCommand();
        $command->identifier = $row['fields'][EudonetParisExtractor::ARRETE_ID];

        $type = $row['fields'][EudonetParisExtractor::ARRETE_TYPE];

        $command->category = RegulationOrderCategoryEnum::OTHER->value;
        $command->otherCategoryText = $type;

        // Adhere to character limit
        $command->description = mb_substr($row['fields'][EudonetParisExtractor::ARRETE_COMPLEMENT_DE_TITRE], 0, 255);

        $command->organization = $organization;

        $startDate = $this->parseDate($row['fields'][EudonetParisExtractor::ARRETE_DATE_DEBUT]);

        if (!$startDate) {
            $error = ['loc' => ['fieldname' => 'ARRETE_DATE_DEBUT'], 'reason' => 'parsing_failed', 'value' => $row['fields'][EudonetParisExtractor::ARRETE_DATE_DEBUT]];

            return [null, $error];
        }

        $command->startDate = $startDate;

        $endDate = $this->parseDate($row['fields'][EudonetParisExtractor::ARRETE_DATE_FIN]);

        if (!$endDate) {
            $error = ['loc' => ['fieldname' => 'ARRETE_DATE_FIN'], 'reason' => 'parsing_failed', 'value' => $row['fields'][EudonetParisExtractor::ARRETE_DATE_FIN]];

            return [null, $error];
        }

        $command->endDate = $endDate;

        return [$command, null];
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

    private function buildLocationCommand(array $row, SaveMeasureCommand $measureCommand): array
    {
        $loc = ['location_id' => $row['fields'][EudonetParisExtractor::LOCALISATION_ID]];

        $arrondissement = $row['fields'][EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT];

        if (!preg_match(self::ARRONDISSEMENT_REGEX, $arrondissement, $matches)) {
            $error = [
                'loc' => [...$loc, 'fieldname' => 'ARRONDISSEMENT'],
                'reason' => 'value_does_not_match_pattern',
                'value' => $arrondissement,
                'pattern' => self::ARRONDISSEMENT_REGEX,
            ];

            return [null, $error];
        }

        $cityCode = sprintf(self::CITY_CODE_TEMPLATE, str_pad($matches['arrondissement'], 2, '0', STR_PAD_LEFT));

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

        $locationCommand = new SaveRegulationLocationCommand();
        $locationCommand->cityCode = $cityCode;
        $locationCommand->cityLabel = self::CITY_LABEL;
        $locationCommand->roadName = $roadName;
        $locationCommand->fromHouseNumber = $fromHouseNumber;
        $locationCommand->toHouseNumber = $toHouseNumber;

        try {
            $locationCommand->geometry = $this->makeLocationGeometry($locationCommand->roadName, $cityCode, $fromHouseNumber, $fromRoadName, $toHouseNumber, $toRoadName);
        } catch (GeocodingFailureException $exc) {
            $error = [
                'loc' => $loc,
                'reason' => 'geocoding_failure',
                'message' => $exc->getMessage(),
                'location_raw' => json_encode($row),
            ];

            return [null, $error];
        }

        $locationCommand->measures[] = $measureCommand;

        return [$locationCommand, null];
    }

    private function makeLocationGeometry(
        string $roadName,
        string $cityCode,
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

        try {
            if ($fromHouseNumber) {
                $fromAddress = sprintf('%s %s', $fromHouseNumber, $roadName);
                $fromPoint = $this->geocoder->computeCoordinates($fromAddress, $cityCode);
            } else {
                $fromPoint = $this->geocoder->computeJunctionCoordinates($roadName, $fromRoadName, $cityCode);
            }

            if ($toHouseNumber) {
                $toAddress = sprintf('%s %s', $toHouseNumber, $roadName);
                $toPoint = $this->geocoder->computeCoordinates($toAddress, $cityCode);
            } else {
                $toPoint = $this->geocoder->computeJunctionCoordinates($roadName, $toRoadName, $cityCode);
            }
        } catch (GeocodingFailureException $exc) {
            throw $exc;
        }

        return GeoJSON::toLineString([$fromPoint, $toPoint]);
    }
}
