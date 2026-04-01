<?php

declare(strict_types=1);

namespace App\Application\Regulation;

interface DatexGeneratorInterface
{
    public function generate(): void;

    public function getCachedDatex(): string;
}
