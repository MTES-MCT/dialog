<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\DTO;

use App\Application\DateUtilsInterface;
use App\Infrastructure\Adapter\DateUtils;

final class MapFilterDTO
{
    public bool $displayPermanentRegulations = true;
    public bool $displayTemporaryRegulations = true;
    public array $measureTypes = [];
    public \DateTimeInterface $startDate;
    public \DateTimeInterface $endDate;
}
