<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis;

use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Infrastructure\IntegrationReport\CommonRecordEnum;
use App\Infrastructure\IntegrationReport\Reporter;

final class LitteralisPeriodParser
{
    // Permet de traiter ces cas :
    // * 'de 08 h 00 à 18 h 00'
    // * '7 h 30 à 17 h 00'
    // * 'DE 7 H 30 à 17 H 00'
    // * '19h à 7h'
    // * 'de 21h à 6h.'
    // * 'de 8 heures à 15 heures 30'
    private const HOURS_REGEX = '/^(?:la nuit |de nuit )?(?:de )?(?P<startHour>\d{1,2}) ?(?:h|heures?) ?(?P<startMinute>\d{1,2})? (?:à|À|0) ?(?P<endHour>\d{1,2}) ?(?:h|heures?) ?(?P<endMinute>\d{1,2})?\.?$/i';

    private $tz;

    public function __construct(?\DateTimeZone $tz = null)
    {
        $this->tz = $tz ?? new \DateTimeZone('Europe/Paris');
    }

    public function parsePeriods(array $parameters, array $properties, Reporter $reporter): array
    {
        $periodCommand = new SavePeriodCommand();

        $this->setPeriodDates($periodCommand, $properties, $reporter);
        $this->normalizePeriodDays($periodCommand);
        $this->setPeriodDaysAndTimes($periodCommand, $parameters, $properties, $reporter);

        return [$periodCommand];
    }

    private function setPeriodDates(SavePeriodCommand $periodCommand, array $properties, Reporter $reporter): void
    {
        $dateFormat = \DateTimeInterface::ISO8601;

        $startDateProperty = $properties['emprisedebut'] ? 'emprisedebut' : 'arretedebut';
        $startDate = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ISO8601, $properties[$startDateProperty]);

        if ($startDate) {
            $periodCommand->startDate = $startDate;
            $periodCommand->startTime = $startDate;
        } else {
            $reporter->addError(
                LitteralisRecordEnum::ERROR_DATE_PARSING_FAILED->value,
                [
                    CommonRecordEnum::ATTR_REGULATION_ID->value => $properties['arretesrcid'],
                    CommonRecordEnum::ATTR_URL->value => $properties['shorturl'],
                    CommonRecordEnum::ATTR_DETAILS->value => [
                        'idemprise' => $properties['idemprise'],
                        $startDateProperty => $properties[$startDateProperty],
                        'format' => $dateFormat,
                    ],
                ],
            );
        }

        $endDateProperty = $properties['emprisefin'] ? 'emprisefin' : 'arretefin';
        $periodCommand->isPermanent = true;

        if (!$properties[$endDateProperty]) {
            return;
        }

        $periodCommand->isPermanent = false;

        $endDate = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ISO8601, $properties[$endDateProperty]);

        if ($endDate) {
            $periodCommand->endDate = $endDate;
            $periodCommand->endTime = $endDate;
        } else {
            $reporter->addError(
                LitteralisRecordEnum::ERROR_DATE_PARSING_FAILED->value,
                [
                    CommonRecordEnum::ATTR_REGULATION_ID->value => $properties['arretesrcid'],
                    CommonRecordEnum::ATTR_URL->value => $properties['shorturl'],
                    CommonRecordEnum::ATTR_DETAILS->value => [
                        'idemprise' => $properties['idemprise'],
                        $endDateProperty => $properties[$endDateProperty],
                        'format' => $dateFormat,
                    ],
                ],
            );
        }
    }

    private function normalizePeriodDays(SavePeriodCommand $periodCommand): void
    {
        if (!$periodCommand->startDate || !$periodCommand->endDate) {
            return;
        }

        // Dans les données Litteralis, une date et heure de début et de fin identiques
        // sont parfois utilisées pour décrire un événement ponctuel, qui a lieu un jour spécifique avec des horaires précisés dans 'jours et horaires'.
        // On gère ce cas en interprétant ça comme étant "toute la journée", donc de minuit à 23h59 ce jour-là.

        if ($periodCommand->startDate == $periodCommand->endDate) {
            $startDate = \DateTimeImmutable::createFromInterface($periodCommand->startDate)
                ->setTimeZone($this->tz)
                ->setTime(0, 0)
                ->setTimeZone(new \DateTimeZone('UTC'));

            $endDate = \DateTimeImmutable::createFromInterface($periodCommand->endDate)
                ->setTimeZone($this->tz)
                ->setTime(23, 59)
                ->setTimeZone(new \DateTimeZone('UTC'));

            $periodCommand->startDate = $startDate;
            $periodCommand->startTime = $startDate;
            $periodCommand->endDate = $endDate;
            $periodCommand->endTime = $endDate;
        }
    }

    private function setPeriodDaysAndTimes(SavePeriodCommand $periodCommand, array $parameters, array $properties, Reporter $reporter): void
    {
        $periodCommand->dailyRange = null;
        $periodCommand->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;

        $value = $this->findParameterValue($parameters, 'jours et horaires');

        if (!$value) {
            $periodCommand->timeSlots = [];

            return;
        }

        $periodCommand->timeSlots = $this->parseTimeSlots($value, $properties, $reporter);
    }

    // Public for testing
    public function parseTimeSlots(string $value, array $properties, Reporter $reporter): ?array
    {
        $value = trim($value);

        // Les "jours et horaires" sont précisés au format texte de multiples manières.
        // On ne peut pas tout supporter mais il y a des formats principaux.

        // Quand ce champ ne contient que des heures, on peut utiliser une regex.
        if (preg_match(self::HOURS_REGEX, $value, $matches)) {
            $startHour = $matches['startHour'];
            $startMinute = !empty($matches['startMinute']) ? $matches['startMinute'] : '00';
            $endHour = $matches['endHour'];
            $endMinute = !empty($matches['endMinute']) ? $matches['endMinute'] : '00';
            $timeSlot = new SaveTimeSlotCommand();
            // Store in UTC because the database column doesn't know about timezones
            $timeSlot->startTime = \DateTimeImmutable::createFromFormat('H:i', "$startHour:$startMinute", $this->tz)->setTimezone(new \DateTimeZone('UTC'));
            $timeSlot->endTime = \DateTimeImmutable::createFromFormat('H:i', "$endHour:$endMinute", $this->tz)->setTimezone(new \DateTimeZone('UTC'));

            return [$timeSlot];
        }

        // D'autres cas non-supportés à date :

        // * 'Lundi, mardi, jeudi, vendredi de 8h à 9h et de 16h à 17h.'
        // * 'la journée'
        // * 'le matin'
        // * 'de 21h00 à 05h00 du 12/02/2024 au 12/04/2024 et du 19/08/2024 au 18/10/2024'
        // * 'les nuits du 05/10/2023, 06/10/2023 et 07/10/2023 de 21h00 à 06h00'
        // * 'de 21h00 à 06h00 du lundi soir au samedi matin'

        $reporter->addError(LitteralisRecordEnum::ERROR_PERIOD_UNPARSABLE->value, [
            CommonRecordEnum::ATTR_REGULATION_ID->value => $properties['arretesrcid'],
            CommonRecordEnum::ATTR_URL->value => $properties['shorturl'],
            CommonRecordEnum::ATTR_DETAILS->value => [
                'idemprise' => $properties['idemprise'],
                'jours et horaires' => $value,
            ],
        ]);

        return [];
    }

    // Utilities

    private function findParameterValue(array $parameters, string $theKey): ?string
    {
        foreach ($parameters as [$key, $value]) {
            if ($key === $theKey) {
                return $value;
            }
        }

        return null;
    }
}
