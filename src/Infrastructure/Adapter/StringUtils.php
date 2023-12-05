<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\StringUtilsInterface;

final class StringUtils implements StringUtilsInterface
{
    public function toKebabCase(string $value): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $value));
    }

    public function normalizeEmail(string $email): string
    {
        return trim(strtolower($email));
    }
}
