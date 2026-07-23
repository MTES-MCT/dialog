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
    // Filtre "Types de véhicules" : les restrictions ciblant les poids-lourds sont masquées par
    // défaut, pour éviter qu'une ville couverte d'interdictions poids-lourds n'apparaisse comme
    // entièrement fermée (cf. #1523). Une restriction est considérée comme spécifique aux
    // poids-lourds lorsque son VehicleSet restreint le type heavyGoodsVehicle.
    public bool $displayHeavyGoodsVehicles = false;
    public array $measureTypes = [
        MeasureTypeEnum::NO_ENTRY->value,
    ];
    public ?\DateTimeInterface $endDate = null;

    public function __construct(
        public ?\DateTimeInterface $startDate = null,
    ) {
    }
}
