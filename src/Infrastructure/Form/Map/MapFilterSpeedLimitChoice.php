<?php

declare(strict_types=1);

namespace App\Infrastructure\Form\Map;

final class MapFilterSpeedLimitChoice
{
    public static function values(): array
    {
        return [30, 50, 70, 80, 90, 110];
    }

    public static function choicesForForm(): array
    {
        $choices = [];
        foreach (self::values() as $value) {
            $choices[\sprintf('%d km/h', $value)] = $value;
        }

        return $choices;
    }
}
