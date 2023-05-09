<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum MeasureTypeEnum: string
{
    case NO_ENTRY = 'noEntry';
    case ALTERNATE_ROAD = 'alternateRoad';
    case ONE_WAY_TRAFFIC = 'oneWayTraffic';

    public static function getFormChoices(): array
    {
        $values = array_column(self::cases(), 'value');
        $choices = [];

        foreach ($values as $value) {
            $choices[sprintf('regulation.measure.type.%s', $value)] = $value;
        }

        return $choices;
    }
}
