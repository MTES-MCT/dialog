<?php

declare(strict_types=1);

namespace App\Infrastructure\Mapper\Transformers;

final class DateTimeTransformers
{
    public static function fromIso(?string $value): ?\DateTimeImmutable
    {
        if (!$value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
