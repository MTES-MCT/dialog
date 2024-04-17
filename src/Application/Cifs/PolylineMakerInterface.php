<?php

declare(strict_types=1);

namespace App\Application\Cifs;

interface PolylineMakerInterface
{
    public function getPolylines(string $geometry): array;
}
