<?php

declare(strict_types=1);

namespace App\Domain\Condition\Enum;

enum OperatorEnum: string
{
    public const AND = 'and';
    public const OR = 'or';
    public const XOR = 'xor';
}
