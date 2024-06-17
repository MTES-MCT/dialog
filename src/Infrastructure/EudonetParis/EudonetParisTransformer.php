<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\User\Organization;

final class EudonetParisTransformer
{
    private const ARRONDISSEMENT_REGEX = '/^(?<arrondissement>\d+)(er|e|ème|eme)\s+arrondissement$/i';
    private const CITY_CODE_TEMPLATE = '751%s';
    private const CITY_LABEL = 'Paris';

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

        $measureCommands = [];

        foreach ($row['measures'] as $measureRow) {
            [$measureCommand, $measureErrors] = $this->buildMeasureCommand($measureRow);

            if (empty($measureCommand)) {
                $errors[] = ['loc' => $loc, 'impact' => 'skip_regulation', 'reason' => 'measure_errors', 'errors' => $measureErrors];

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
        $errors = [];

        $locationCommands = [];

        foreach ($row['locations'] as $locationRow) {
            [$locationCommand, $error] = $this->buildLocationCommand($locationRow);

            if (empty($locationCommand)) {
                $errors[] = ['loc' => [...$loc, ...$error['loc']], ...array_diff_key($error, ['loc' => '']), 'impact' => 'skip_measure'];

                return [null, $errors];
            }

            $locationCommands[] = $locationCommand;
        }

        $periodCommands = $this->parsePeriods($row);

        $vehicleSet = new SaveVehicleSetCommand();
        $vehicleSet->allVehicles = true;

        $command = new SaveMeasureCommand();
        $command->type = MeasureTypeEnum::NO_ENTRY->value;
        $command->vehicleSet = $vehicleSet;
        $command->locations = $locationCommands;
        $command->periods = $periodCommands;

        return [$command, null];
    }

    private function buildLocationCommand(array $row): array
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

        return [$locationCommand, null];
    }

    private function parsePeriods(array $row): array
    {
        // The "Alinéa" field contains info on the measure's dates and times which we can use to build Periods.
        $alinea = $row['fields'][EudonetParisExtractor::MESURE_ALINEA];

        if (!$alinea) {
            // No info => no periods.
            return [];
        }

        // We try to find dates and times in "Alinéa".
        $datesAndTimes = $this::parsePeriodsInText($alinea);

        if (!$datesAndTimes) {
            // No dates and times found => assume no periods.
            // (There may be false negatives, for example dates and times in exotic formats, but we cannot detect this.)
            return [];
        }

        // Build periods from dates and times found.
        // We may miss some dates and times due to exotic formats, which could result in misinterpretation of the source
        // data, but we cannot detect this, so we treat this as an acceptable data quality compromise.
        $periodCommands = [];

        foreach ($datesAndTimes as $item) {
            $periodCommand = new SavePeriodCommand();
            $periodCommand->startDate = $item['startDate'];
            $periodCommand->startTime = $item['startTime'];
            $periodCommand->endDate = $item['endDate'];
            $periodCommand->endTime = $item['endTime'];

            if ($item['days']) {
                $periodCommand->recurrenceType = PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value;
                $dailyRange = new SaveDailyRangeCommand();
                $dailyRange->applicableDays = $item['days'];
                $periodCommand->dailyRange = $dailyRange;
            } else {
                $periodCommand->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
            }

            $timeSlots = [];
            $periodCommand->timeSlots = $timeSlots;

            $periodCommands[] = $periodCommand;
        }

        return $periodCommands;
    }

    private static function parsePeriodsInText(string $text): array
    {
        $text = strtolower($text);

        $items = [];

        $frenchDateFmt = new \IntlDateFormatter(
            'fr-FR',
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::MEDIUM,
            'Europe/Paris',
            \IntlDateFormatter::GREGORIAN,
            'dd MMMM y',
        );

        // Combined days in the same month
        // "du 16 au 20 janvier 2023"
        $daySpanPattern = '/(?P<startDayNumber>\d{1,2}) au (?P<endDayNumber>\d{1,2}) (?P<monthName>[a-z]+) (?P<year>\d{4})/';
        $matchCount = preg_match_all($daySpanPattern, $text, $matches);
        if ($matchCount > 0) {
            dump($matches);
            foreach (range(0, $matchCount - 1) as $index) {
                $startDayNumber = $matches['startDayNumber'][$index];
                $endDayNumber = $matches['endDayNumber'][$index];
                $monthName = $matches['monthName'][$index];
                $year = $matches['year'][$index];

                $startDateTimestamp = $frenchDateFmt->parse(sprintf('%s %s %s', $startDayNumber, $monthName, $year));
                $endDateTimestamp = $frenchDateFmt->parse(sprintf('%s %s %s', $endDayNumber, $monthName, $year));

                $items[] = [
                    'startDate' => \DateTimeImmutable::createFromFormat('U', (string) $startDateTimestamp),
                    'startTime' => \DateTimeImmutable::createFromFormat('H:i:s', '00:00:00'),
                    'endDate' => \DateTimeImmutable::createFromFormat('U', (string) $endDateTimestamp),
                    'endTime' => \DateTimeImmutable::createFromFormat('H:i:s', '23:59:00'),
                    'days' => [],
                ];
            }

            $text = preg_replace($daySpanPattern, '', $text);
        }

        return $items;
    }
}
