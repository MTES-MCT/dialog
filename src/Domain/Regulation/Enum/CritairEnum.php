<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

// See: https://www.legifrance.gouv.fr/loda/id/JORFTEXT000032749723/
enum CritairEnum: string
{
    case CRITAIR_0 = 'critair0';
    case CRITAIR_1 = 'critair1';
    case CRITAIR_2 = 'critair2';
    case CRITAIR_3 = 'critair3';
    case CRITAIR_4 = 'critair4';
    case CRITAIR_5 = 'critair5';
    case CRITAIR_WITHOUT = 'critairWithout';

    public static function critairCases(): array
    {
        return [
            'Sans vignette' => CritairEnum::CRITAIR_WITHOUT,
            5 => CritairEnum::CRITAIR_5,
            4 => CritairEnum::CRITAIR_4,
            3 => CritairEnum::CRITAIR_3,
            2 => CritairEnum::CRITAIR_2,
            1 => CritairEnum::CRITAIR_1,
            '0 / VE' => CritairEnum::CRITAIR_0,
        ];
    }

    public static function critairValues(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::critairCases());
    }
}
