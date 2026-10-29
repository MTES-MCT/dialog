<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

/**
 * Jours fériés français (Code du travail, art. L3133-1),
 * avec les jours fériés supplémentaires d'Alsace-Moselle (art. L3134-13).
 */
final class PublicHolidayCalendar
{
    private const ALSACE_MOSELLE_DEPARTMENT_CODES = ['57', '67', '68'];

    /**
     * @return string[] Dates au format Y-m-d, triées
     */
    public static function getDates(int $year, ?string $departmentCode = null): array
    {
        $easter = self::getEasterSunday($year);

        $dates = [
            \sprintf('%d-01-01', $year),
            $easter->modify('+1 day')->format('Y-m-d'), // Lundi de Pâques
            \sprintf('%d-05-01', $year),
            \sprintf('%d-05-08', $year),
            $easter->modify('+39 days')->format('Y-m-d'), // Ascension
            $easter->modify('+50 days')->format('Y-m-d'), // Lundi de Pentecôte
            \sprintf('%d-07-14', $year),
            \sprintf('%d-08-15', $year),
            \sprintf('%d-11-01', $year),
            \sprintf('%d-11-11', $year),
            \sprintf('%d-12-25', $year),
        ];

        if (\in_array($departmentCode, self::ALSACE_MOSELLE_DEPARTMENT_CODES, true)) {
            $dates[] = $easter->modify('-2 days')->format('Y-m-d'); // Vendredi saint
            $dates[] = \sprintf('%d-12-26', $year); // Saint-Étienne
        }

        sort($dates);

        return $dates;
    }

    // Algorithme anonyme grégorien (Meeus/Jones/Butcher)
    private static function getEasterSunday(int $year): \DateTimeImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new \DateTimeImmutable(\sprintf('%d-%02d-%02d', $year, $month, $day));
    }
}
