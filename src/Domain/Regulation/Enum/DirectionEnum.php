<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum DirectionEnum: string
{
    case BOTH = 'BOTH';
    case A_TO_B = 'A_TO_B';
    case B_TO_A = 'B_TO_A';

    /**
     * Sens au format DATEX (loc:LinearDirectionEnum), relatif à l'ordre des sommets de la géométrie,
     * elle-même orientée du point A vers le point B. Null si aucun sens n'est connu.
     */
    public static function toDatexDirection(?string $value): ?string
    {
        return match ($value) {
            self::BOTH->value => 'both',
            self::A_TO_B->value => 'aligned',
            self::B_TO_A->value => 'opposite',
            default => null,
        };
    }

    /**
     * Sens opposé à un sens unique (A_TO_B ou B_TO_A). Null en double sens ou sans sens connu.
     */
    public static function opposite(?string $value): ?string
    {
        return match ($value) {
            self::A_TO_B->value => self::B_TO_A->value,
            self::B_TO_A->value => self::A_TO_B->value,
            default => null,
        };
    }

    public static function toCifsDirection(string $value): string
    {
        return match ($value) {
            self::BOTH->value => 'BOTH_DIRECTIONS',
            // Dans les autres cas, le sens est indiqué par l'ordre des points dans la <polyline>
            default => 'ONE_DIRECTION',
        };
    }
}
