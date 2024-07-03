<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\DTO;

final class MapFilterDTO
{
    public bool $displayPermanentRegulations = true;
    public bool $displayTemporaryRegulations = true;
    public bool $displayFutureRegulations = false;
    public bool $displayPastRegulations = false;
    public float $zoomLevel = 5;
}
