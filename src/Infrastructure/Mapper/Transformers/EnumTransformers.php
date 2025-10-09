<?php

declare(strict_types=1);

namespace App\Infrastructure\Mapper\Transformers;

final class EnumTransformers
{
    public static function toString(?\BackedEnum $value): ?string
    {
        if (!$value) {
            return null;
        }

        return (string) $value->value;
    }
}
