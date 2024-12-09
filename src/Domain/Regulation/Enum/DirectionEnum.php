<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum DirectionEnum: string
{
    case BOTH = 'BOTH';
    case A_TO_B = 'A_TO_B';
    case B_TO_A = 'B_TO_A';

    public static function toCifsDirection(string $value): string
    {
        return match ($value) {
            self::BOTH->value => 'BOTH_DIRECTIONS',
            // Dans les autres cas, le sens est indiqué par l'ordre des points dans la <polyline>
            default => 'ONE_DIRECTION',
        };
    }
}
