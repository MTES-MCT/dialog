<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Enum;

enum CritairEnum: string
{
    case CRITAIR_VE = 'critairVE';
    case CRITAIR_1 = 'critair1';
    case CRITAIR_2 = 'critair2'; // See: https://www.legifrance.gouv.fr/loda/id/JORFTEXT000032749723/
    case CRITAIR_3 = 'critair3'; // See: https://www.legifrance.gouv.fr/loda/id/JORFTEXT000032749723/
    case CRITAIR_4 = 'critair4'; // See: https://www.legifrance.gouv.fr/loda/id/JORFTEXT000032749723/
    case CRITAIR_5 = 'critair5'; // See: https://www.legifrance.gouv.fr/loda/id/JORFTEXT000032749723/

    public static function critairCases(): array
    {
        return [
            5 => CritairEnum::CRITAIR_5,
            4 => CritairEnum::CRITAIR_4,
            3 => CritairEnum::CRITAIR_3,
            2 => CritairEnum::CRITAIR_2,
            1 => CritairEnum::CRITAIR_1,
            'VE' => CritairEnum::CRITAIR_VE,
        ];
    }
}
