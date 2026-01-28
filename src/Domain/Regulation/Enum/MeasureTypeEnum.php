<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum MeasureTypeEnum: string
{
    case ALTERNATE_ROAD = 'alternateRoad';
    case NO_ENTRY = 'noEntry';
    case SPEED_LIMITATION = 'speedLimitation';
    case PARKING_PROHIBITED = 'parkingProhibited';

    public function getCifsKey(): string
    {
        return match ($this) {
            self::ALTERNATE_ROAD => 'HAZARD_ON_ROAD_LANE_CLOSED',
            self::NO_ENTRY => 'ROAD_CLOSED',
            self::SPEED_LIMITATION => 'SPEED_LIMIT',
            self::PARKING_PROHIBITED => 'PARKING_PROHIBITED',
        };
    }

    /**
     * @return array<string>
     */
    public static function toArray(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
