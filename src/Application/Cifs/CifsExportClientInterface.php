<?php

declare(strict_types=1);

namespace App\Application\Cifs;

interface CifsExportClientInterface
{
    public function getIncidentsCount(): int;
}
