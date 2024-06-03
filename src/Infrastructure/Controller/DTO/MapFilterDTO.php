<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\DTO;

final class MapFilterDTO
{
    public ?string $category = null;
    public ?string $displayFutureRegulations = null;
    public ?string $displayPastRegulations = null;
}
