<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\DTO;

final class MapFilterDTO
{
    public bool $displayPermanentRegulations = false;
    public bool $displayTemporaryRegulations = true;
    public bool $displayMeasureTypeNoEntry = false;
    public bool $displayMeasureTypeSpeedLimitation = false;
}
