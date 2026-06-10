<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\DTO;

use App\Domain\Regulation\Enum\MeasureTypeEnum;

final class MapFilterDTO
{
    public bool $displayPermanentRegulations = true;
    public bool $displayTemporaryRegulations = true;
    // "Statut des arrêtés" filter (logged-in users only): published shown by default,
    // the organization's own drafts hidden by default.
    public bool $displayPublished = true;
    public bool $displayDrafts = false;
    public array $measureTypes = [
        MeasureTypeEnum::NO_ENTRY->value,
    ];
    public ?\DateTimeInterface $endDate = null;

    public function __construct(
        public ?\DateTimeInterface $startDate = null,
    ) {
    }
}
