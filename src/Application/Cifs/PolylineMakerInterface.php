<?php

declare(strict_types=1);

namespace App\Application\Cifs;

interface PolylineMakerInterface
{
    public function attemptMergeLines(string $geometry): ?string;

    public function getPolylines(string $geometry): array;
}
