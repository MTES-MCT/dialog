<?php

declare(strict_types=1);

namespace App\Domain\TrafficRegulation\Condition\Enum;

enum OperatorEnum: string
{
    case AND = 'and';
    case OR = 'or';
    case XOR = 'xor';
}
