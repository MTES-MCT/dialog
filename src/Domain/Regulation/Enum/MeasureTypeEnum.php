<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum MeasureTypeEnum: string
{
    case ALTERNATE_ROAD = 'alternateRoad';
    case NO_OVERTAKING = 'noOvertaking';
    case NO_ENTRY = 'noEntry';
    case SPEED_LIMITATION = 'speedLimitation';
    case PARKING_PROHIBITED = 'parkingProhibited';
    // Mise en sens unique : la circulation n'est autorisée que dans le sens indiqué par chaque emprise
    case ONE_WAY = 'oneWay';

    public function getCifsKey(): string
    {
        return match ($this) {
            self::ALTERNATE_ROAD => 'HAZARD',
            self::NO_ENTRY => 'ROAD_CLOSED',
            // Un sens unique ferme le sens opposé au sens autorisé
            self::ONE_WAY => 'ROAD_CLOSED',
            self::SPEED_LIMITATION => 'HAZARD_ON_ROAD',
            default => 'HAZARD',
        };
    }
}
