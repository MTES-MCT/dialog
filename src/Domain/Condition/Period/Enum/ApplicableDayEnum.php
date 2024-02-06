<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period\Enum;

// We use the DATEXII DayEnum values [docs/spec/datex2/DATEXII_3_Common.xsd]
enum ApplicableDayEnum: string
{
    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';
    case SUNDAY = 'sunday';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getDayIndex(string $day): ?int
    {
        $key = array_search($day, self::getValues());

        return \is_int($key) ? $key : null;
    }

    public static function getByIndex(int $index): ?string
    {
        $values = self::getValues();

        if (0 <= $index && $index < \count($values)) {
            return $values[$index];
        }

        return null;
    }

    public static function hasAllValues(array $arr): bool
    {
        $diff = array_diff(self::getValues(), $arr);

        return \count($diff) === 0;
    }
}
