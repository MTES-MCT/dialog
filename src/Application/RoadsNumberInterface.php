<?php

declare(strict_types=1);

namespace App\Application;

interface RoadsNumberInterface
{
    public function getRoadsNumber(string $gestionnaire, string $type_de_route): array;
}
