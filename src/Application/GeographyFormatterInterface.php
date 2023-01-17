<?php

declare(strict_types=1);

namespace App\Application;

interface GeographyFormatterInterface
{
    public function formatPoint(float $latitude, float $longitude): string;
}
