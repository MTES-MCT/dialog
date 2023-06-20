<?php

declare(strict_types=1);

namespace App\Application;

interface StringUtilsInterface
{
    public function toKebabCase(string $value): string;
}
