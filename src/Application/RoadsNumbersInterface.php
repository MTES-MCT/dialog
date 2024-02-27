<?php

declare(strict_types=1);

namespace App\Application;

interface RoadsNumbersInterface
{
    public function getDepartmentalRoad(string $search, string $gestionnaire, string $roadType): array;
}
