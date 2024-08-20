<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis;

use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;

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

    public function parse(string $value, ?\DateTimeZone $tz = null): ?array
    {
        $tz ??= $this->tz;

        $value = trim($value);

        // Les "jours et horaires" sont précisés au format texte de multiples manières.
        // On ne peut pas tout supporter mais il y a des formats principaux.

        // Quand ce champ ne contient que des heures, on peut utiliser une regex.
        if (preg_match(self::HOURS_REGEX, $value, $matches)) {
            $startHour = $matches['startHour'];
            $startMinute = ($matches['startMinute'] ?? '') ?: '00';
            $endHour = $matches['endHour'];
            $endMinute = ($matches['endMinute'] ?? '') ?: '00';

            $timeSlot = new SaveTimeSlotCommand();
            // Store in UTC because the database column doesn't know about timezones
            $timeSlot->startTime = \DateTimeImmutable::createFromFormat('H:i', "$startHour:$startMinute", $tz)->setTimezone(new \DateTimeZone('UTC'));
            $timeSlot->endTime = \DateTimeImmutable::createFromFormat('H:i', "$endHour:$endMinute", $tz)->setTimezone(new \DateTimeZone('UTC'));

            return ['timeSlots' => [$timeSlot]];
        }

        // D'autres cas non-supportés à date :

        // * 'Lundi, mardi, jeudi, vendredi de 8h à 9h et de 16h à 17h.'
        // * 'la journée'
        // * 'le matin'
        // * 'de 21h00 à 05h00 du 12/02/2024 au 12/04/2024 et du 19/08/2024 au 18/10/2024'
        // * 'les nuits du 05/10/2023, 06/10/2023 et 07/10/2023 de 21h00 à 06h00'
        // * 'de 21h00 à 06h00 du lundi soir au samedi matin'

        return null;
    }
}
