<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\BacIdf;

interface BacIdfCityProcessorInterface
{
    public function getSiretFromInseeCode(string $inseeCode): ?string;
}
