<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\User\Organization;

final class EudonetParisTransformer
{
    private const ARRONDISSEMENT_REGEX = '/^(?<arrondissement>\d+)(er|e|ème|eme)\s+arrondissement$/i';
    private const CITY_CODE_TEMPLATE = '751%s';
    private const CITY_LABEL = 'Paris';

    public function transform(array $row, Organization $organization, EudonetParisReporter $reporter): ?ImportEudonetParisRegulationCommand
    {
        $regulationId = $row['fields'][EudonetParisExtractor::ARRETE_ID];

        // if no measures (after filtering), we do not import the regulation
        if (\count($row['measures']) === 0) {
            // $errors[] = ['loc' => $loc, 'impact' => 'skip_regulation', 'reason' => EudonetParisErrorEnum::NO_MEASURES_FOUND->value];
            $reporter->addNotice($reporter::NOTICE_NO_MEASURES_FOUND, [
                'regulation_id' => $regulationId,
            ]);

            return null;
        }

        $generalInfoCommand = $this->buildGeneralInfoCommand($row, $organization, $reporter);

        /*if ($reporter->hasNewErrors()) {
            // $errors[] = ['loc' => [...$loc, ...$error['loc']], 'impact' => 'skip_regulation', ...$error];
            return null;
        }*/

        $measureCommands = [];

        foreach ($row['measures'] as $measureRow) {
            $measureCommand = $this->buildMeasureCommand($measureRow, $regulationId, $reporter);

            /*if (empty($measureCommand)) {
                $errors[] = ['loc' => $loc, 'impact' => 'skip_regulation', 'reason' => EudonetParisErrorEnum::MEASURE_ERRORS->value, 'errors' => $measureErrors];

                return null;
            }*/

            $measureCommands[] = $measureCommand;
        }

        // we return null if we got one or more errors
        if ($reporter->hasNewErrors()) {
            return null;
        }

        $command = new ImportEudonetParisRegulationCommand(
            $generalInfoCommand,
            $measureCommands,
        );

        return $command;
    }

    private function parseDate(string $value): ?\DateTimeInterface
    {
        if ($date = \DateTimeImmutable::createFromFormat('Y/m/d H:i:s', $value, new \DateTimeZone('Europe/Paris'))) {
            return $date;
        }

        if (\DateTimeImmutable::createFromFormat('Y/m/d', $value, new \DateTimeZone('Europe/Paris'))) {
            // Need to add a datetime otherwise PHP would use the current server time, not midnight.
            return $this->parseDate($value . ' 00:00:00');
        }

        if (\DateTimeImmutable::createFromFormat('d/m/Y', $value, new \DateTimeZone('Europe/Paris'))) {
            // This format is somtimes used by some Eudonet Paris data input users.
            // Again, we need to ensure there is a datetime.
            return \DateTimeImmutable::createFromFormat('d/m/Y H:i:s', $value . ' 00:00:00', new \DateTimeZone('Europe/Paris'));
        }

        return null;
    }

    private function buildGeneralInfoCommand(array $row, Organization $organization, EudonetParisReporter $reporter): SaveRegulationGeneralInfoCommand
    {
        $regulationId = $row['fields'][EudonetParisExtractor::ARRETE_ID];

        $command = new SaveRegulationGeneralInfoCommand();
        $command->identifier = $regulationId;

        $type = $row['fields'][EudonetParisExtractor::ARRETE_TYPE];

        $command->category = RegulationOrderCategoryEnum::OTHER->value;
        $command->otherCategoryText = $type;

        // Adhere to character limit
        $command->description = mb_substr($row['fields'][EudonetParisExtractor::ARRETE_COMPLEMENT_DE_TITRE], 0, 255);

        $command->organization = $organization;

        $regulationStartDate = $row['fields'][EudonetParisExtractor::ARRETE_DATE_DEBUT]; // start date before parsing, useful to debug
        $startDate = $this->parseDate($regulationStartDate);

        if (!$startDate) {
            // $error = ['loc' => ['fieldname' => 'ARRETE_DATE_DEBUT'], 'reason' => EudonetParisErrorEnum::PARSING_FAILED->value, 'value' => $row['fields'][EudonetParisExtractor::ARRETE_DATE_DEBUT]];
            $reporter->addError($reporter::ERROR_REGULATION_START_DATE_PARSING_FAILED, [
                'regulation_id' => $regulationId,
                'regulation_start_date' => $regulationStartDate,
            ]);
        }

        $command->startDate = $startDate;

        $regulationEndDate = $row['fields'][EudonetParisExtractor::ARRETE_DATE_FIN]; // end date before parsing, useful to debug
        $endDate = $this->parseDate($regulationEndDate);

        if (!$endDate) {
            // $error = ['loc' => ['fieldname' => 'ARRETE_DATE_FIN'], 'reason' => EudonetParisErrorEnum::PARSING_FAILED->value, 'value' => $row['fields'][EudonetParisExtractor::ARRETE_DATE_FIN]];
            $reporter->addError($reporter::ERROR_REGULATION_END_DATE_PARSING_FAILED, [
                'regulation_id' => $regulationId,
                'regulation_end_date' => $regulationEndDate,
            ]);
        }

        $command->endDate = $endDate;

        return $command;
    }

    private function buildMeasureCommand(array $row, string $regulationId, EudonetParisReporter $reporter): SaveMeasureCommand
    {
        // $loc = ['measure_id' => $row['fields'][EudonetParisExtractor::MESURE_ID]];
        $measureId = $row['fields'][EudonetParisExtractor::MESURE_ID];
        $alinea = $row['fields'][EudonetParisExtractor::MESURE_ALINEA];

        if ($alinea) {
            // This "alinea" field contains free-form text with indications on temporal validity.
            // We cannot parse this data so we only ingest measures that do NOT have an "alinea", meaning their dates are
            // the same as the global regulation order dates.
            // $errors[] = ['loc' => $loc, 'reason' => EudonetParisErrorEnum::MEASURE_MAY_CONTAIN_DATES->value, 'alinea' => $alinea, 'impact' => 'skip_measure'];
            $reporter->addError($reporter::ERROR_EUDONET_MEASURE_ALINEA_MAY_CONTAIN_DATES, [
                'regulation_id' => $regulationId,
                'measure_id' => $measureId,
                'alinea' => $alinea,
            ]);

            // return null;
        }

        $locationCommands = [];

        foreach ($row['locations'] as $locationRow) {
            $locationCommand = $this->buildLocationCommand($locationRow, $regulationId, $measureId, $reporter);

            /*if (empty($locationCommand)) {
                $errors[] = ['loc' => [...$loc, ...$error['loc']], ...array_diff_key($error, ['loc' => '']), 'impact' => 'skip_measure'];

                continue;
            }*/

            $locationCommands[] = $locationCommand;
        }

        $vehicleSet = new SaveVehicleSetCommand();
        $vehicleSet->allVehicles = true;

        $command = new SaveMeasureCommand();
        $command->type = MeasureTypeEnum::NO_ENTRY->value;
        $command->vehicleSet = $vehicleSet;
        $command->locations = $locationCommands;

        return $command;
    }

    private function buildLocationCommand(array $row, string $regulationId, string $measureId, EudonetParisReporter $reporter): SaveLocationCommand
    {
        $locationId = $row['fields'][EudonetParisExtractor::LOCALISATION_ID];
        $arrondissement = $row['fields'][EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT];

        if (!preg_match(self::ARRONDISSEMENT_REGEX, $arrondissement, $matches)) {
            $reporter->addError($reporter::ERROR_VALUE_DOES_NOT_MATCH_PATTERN, [
                'regulation_id' => $regulationId,
                'measure_id' => $measureId,
                'location_id' => $locationId,
                'fieldname' => 'ARRONDISSEMENT',
                'value' => $arrondissement,
                'pattern' => self::ARRONDISSEMENT_REGEX,
            ]);
            // return null;
            $cityCode = null;
        } else {
            $cityCode = \sprintf(self::CITY_CODE_TEMPLATE, str_pad($matches['arrondissement'], 2, '0', STR_PAD_LEFT));
        }

        $porteSur = $row['fields'][EudonetParisExtractor::LOCALISATION_PORTE_SUR];
        $libelleVoie = $row['fields'][EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE];
        $libelleVoieDebut = $row['fields'][EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_DEBUT];
        $libelleVoieFin = $row['fields'][EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_FIN];
        $numAdresseDebut = $row['fields'][EudonetParisExtractor::LOCALISATION_N_ADRESSE_DEBUT];
        $numAdresseFin = $row['fields'][EudonetParisExtractor::LOCALISATION_N_ADRESSE_FIN];

        $roadName = null;

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
            $reporter->addError($reporter::ERROR_UNSUPPORTED_LOCATION_FIELDSET, [
                'regulation_id' => $regulationId,
                'measure_id' => $measureId,
                'location_id' => $locationId,
                'location_raw' => json_encode($row),
            ]);

            // return null;
        }

        $locationCommand = new SaveLocationCommand();
        $locationCommand->roadType = RoadTypeEnum::LANE->value;
        $locationCommand->namedStreet = new SaveNamedStreetCommand();
        $locationCommand->namedStreet->roadType = RoadTypeEnum::LANE->value;
        $locationCommand->namedStreet->cityCode = $cityCode;
        $locationCommand->namedStreet->cityLabel = self::CITY_LABEL;
        $locationCommand->namedStreet->roadName = $roadName;
        $locationCommand->namedStreet->fromHouseNumber = $fromHouseNumber;
        $locationCommand->namedStreet->fromRoadName = $fromRoadName;
        $locationCommand->namedStreet->fromCoords = empty($row['fromCoords']) ? null : $row['fromCoords'];
        $locationCommand->namedStreet->toHouseNumber = $toHouseNumber;
        $locationCommand->namedStreet->toRoadName = $toRoadName;
        $locationCommand->namedStreet->toCoords = empty($row['toCoords']) ? null : $row['toCoords'];

        return $locationCommand;
    }
}
