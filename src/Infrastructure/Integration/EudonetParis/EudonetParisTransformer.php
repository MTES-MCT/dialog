<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\EudonetParis;

use App\Application\GeocoderInterface;
use App\Application\Integration\EudonetParis\Command\ImportEudonetParisRegulationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationSubjectEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\User\Organization;
use App\Infrastructure\Integration\EudonetParis\Enum\EudonetParisErrorEnum;

final class EudonetParisTransformer
{
    private const ARRONDISSEMENT_REGEX = '/^(?<arrondissement>\d+)(er|e|ème|eme)\s+arrondissement$/i';
    private const CITY_CODE_TEMPLATE = '751%s';
    private const CITY_LABEL = 'Paris';

    public function __construct(private GeocoderInterface $geocoder)
    {
    }

    public function transform(array $row, Organization $organization): EudonetParisTransformerResult
    {
        $errors = [];
        $loc = ['regulation_identifier' => $row['fields'][EudonetParisExtractor::ARRETE_ID]];

        if (\count($row['measures']) === 0) {
            $errors[] = ['loc' => $loc, 'impact' => 'skip_regulation', 'reason' => EudonetParisErrorEnum::NO_MEASURES_FOUND->value];

            return new EudonetParisTransformerResult(null, $errors);
        }

        [$generalInfoCommand, $regulationPeriodCommand, $error] = $this->buildGeneralInfoCommand($row, $organization);

        if ($error) {
            $errors[] = ['loc' => [...$loc, ...$error['loc']], 'impact' => 'skip_regulation', ...$error];

            return new EudonetParisTransformerResult(null, $errors);
        }

        $measureCommands = [];

        foreach ($row['measures'] as $measureRow) {
            [$measureCommand, $measureErrors] = $this->buildMeasureCommand($measureRow, $regulationPeriodCommand, $organization);

            if (empty($measureCommand)) {
                $errors[] = ['loc' => $loc, 'impact' => 'skip_regulation', 'reason' => EudonetParisErrorEnum::MEASURE_ERRORS->value, 'errors' => $measureErrors];

                return new EudonetParisTransformerResult(null, $errors);
            }

            $measureCommands[] = $measureCommand;
        }

        $command = new ImportEudonetParisRegulationCommand(
            $generalInfoCommand,
            $measureCommands,
        );

        return new EudonetParisTransformerResult($command, $errors);
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

    private function buildGeneralInfoCommand(array $row, Organization $organization): array
    {
        $command = new SaveRegulationGeneralInfoCommand();
        $command->identifier = $row['fields'][EudonetParisExtractor::ARRETE_ID];

        $type = $row['fields'][EudonetParisExtractor::ARRETE_TYPE];

        $command->category = RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value;
        $command->subject = RegulationSubjectEnum::OTHER->value;
        $command->otherCategoryText = $type;

        // Adhere to character limit
        $command->title = mb_substr($row['fields'][EudonetParisExtractor::ARRETE_COMPLEMENT_DE_TITRE], 0, 255);

        $command->organization = $organization;

        $regulationPeriodCommand = new SavePeriodCommand();
        $regulationPeriodCommand->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
        $regulationPeriodCommand->dailyRange = null;
        $regulationPeriodCommand->timeSlots = [];

        $startDate = $this->parseDate($row['fields'][EudonetParisExtractor::ARRETE_DATE_DEBUT]);

        if (!$startDate) {
            $error = ['loc' => ['fieldname' => 'ARRETE_DATE_DEBUT'], 'reason' => EudonetParisErrorEnum::PARSING_FAILED->value, 'value' => $row['fields'][EudonetParisExtractor::ARRETE_DATE_DEBUT]];

            return [null, null, $error];
        }

        $regulationPeriodCommand->startDate = $startDate;
        $regulationPeriodCommand->startTime = $startDate;

        $endDate = $this->parseDate($row['fields'][EudonetParisExtractor::ARRETE_DATE_FIN]);

        if (!$endDate) {
            $error = ['loc' => ['fieldname' => 'ARRETE_DATE_FIN'], 'reason' => EudonetParisErrorEnum::PARSING_FAILED->value, 'value' => $row['fields'][EudonetParisExtractor::ARRETE_DATE_FIN]];

            return [null, null, $error];
        }

        $regulationPeriodCommand->endDate = $endDate;
        $regulationPeriodCommand->endTime = $endDate;

        return [$command, $regulationPeriodCommand, null];
    }

    private function buildMeasureCommand(array $row, SavePeriodCommand $regulationPeriodCommand, Organization $organization): array
    {
        $loc = ['measure_id' => $row['fields'][EudonetParisExtractor::MESURE_ID]];
        $errors = [];

        $alinea = $row['fields'][EudonetParisExtractor::MESURE_ALINEA];

        if ($alinea) {
            // This "alinea" field contains free-form text with indications on temporal validity.
            // We cannot parse this data so we only ingest measures that do NOT have an "alinea", meaning their dates are
            // the same as the global regulation order dates.
            $errors[] = ['loc' => $loc, 'reason' => EudonetParisErrorEnum::MEASURE_MAY_CONTAIN_DATES->value, 'alinea' => $alinea, 'impact' => 'skip_measure'];

            return [null, $errors];
        }

        $locationCommands = [];

        foreach ($row['locations'] as $locationRow) {
            [$locationCommand, $error] = $this->buildLocationCommand($locationRow, $organization);

            if (empty($locationCommand)) {
                $errors[] = ['loc' => [...$loc, ...$error['loc']], ...array_diff_key($error, ['loc' => '']), 'impact' => 'skip_measure'];

                return [null, $errors];
            }

            $locationCommands[] = $locationCommand;
        }

        $vehicleSet = new SaveVehicleSetCommand();
        $vehicleSet->allVehicles = true;

        $command = new SaveMeasureCommand();
        $command->type = MeasureTypeEnum::NO_ENTRY->value;
        $command->vehicleSet = $vehicleSet;
        $command->locations = $locationCommands;
        $command->periods[] = $regulationPeriodCommand;

        return [$command, null];
    }

    private function buildLocationCommand(array $row, Organization $organization): array
    {
        $loc = ['location_id' => $row['fields'][EudonetParisExtractor::LOCALISATION_ID]];

        $arrondissement = $row['fields'][EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT];

        if (!preg_match(self::ARRONDISSEMENT_REGEX, $arrondissement, $matches)) {
            $error = [
                'loc' => [...$loc, 'fieldname' => 'ARRONDISSEMENT'],
                'reason' => EudonetParisErrorEnum::VALUE_DOES_NOT_MATCH_PATTERN->value,
                'value' => $arrondissement,
                'pattern' => self::ARRONDISSEMENT_REGEX,
            ];

            return [null, $error];
        }

        $cityCode = \sprintf(self::CITY_CODE_TEMPLATE, str_pad($matches['arrondissement'], 2, '0', STR_PAD_LEFT));
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
                'reason' => EudonetParisErrorEnum::UNSUPPORTED_LOCATION_FIELDSET->value,
                'location_raw' => json_encode($row),
            ];

            return [null, $error];
        }

        $roadBanId = $this->geocoder->getRoadBanIdOrError($roadName, $cityCode);
        $fromRoadBanId = $fromRoadName ? $this->geocoder->getRoadBanIdOrError($fromRoadName, $cityCode) : null;
        $toRoadBanId = $toRoadName ? $this->geocoder->getRoadBanIdOrError($toRoadName, $cityCode) : null;

        $locationCommand = new SaveLocationCommand();
        $locationCommand->organization = $organization;
        $locationCommand->roadType = RoadTypeEnum::LANE->value;
        $locationCommand->namedStreet = new SaveNamedStreetCommand();
        $locationCommand->namedStreet->roadType = RoadTypeEnum::LANE->value;
        $locationCommand->namedStreet->cityCode = $cityCode;
        $locationCommand->namedStreet->cityLabel = self::CITY_LABEL;
        $locationCommand->namedStreet->roadBanId = $roadBanId;
        $locationCommand->namedStreet->roadName = $roadName;
        $locationCommand->namedStreet->fromHouseNumber = $fromHouseNumber;
        $locationCommand->namedStreet->fromRoadBanId = $fromRoadBanId;
        $locationCommand->namedStreet->fromRoadName = $fromRoadName;
        $locationCommand->namedStreet->fromCoords = empty($row['fromCoords']) ? null : $row['fromCoords'];
        $locationCommand->namedStreet->toHouseNumber = $toHouseNumber;
        $locationCommand->namedStreet->toRoadBanId = $toRoadBanId;
        $locationCommand->namedStreet->toRoadName = $toRoadName;
        $locationCommand->namedStreet->toCoords = empty($row['toCoords']) ? null : $row['toCoords'];

        return [$locationCommand, null];
    }
}
