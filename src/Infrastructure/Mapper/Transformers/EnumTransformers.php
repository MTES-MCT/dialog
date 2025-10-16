<?php

declare(strict_types=1);

namespace App\Infrastructure\Mapper\Transformers;

final class EnumTransformers
{
    public static function toString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }
}
