<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

/**
 * Localisation (emprise) envoyée via l'API d'écriture mais rejetée lors de l'intégration de l'arrêté
 * (valeur manquante, géocodage en échec, hors compétence...). Les autres localisations de l'arrêté
 * ont été intégrées.
 */
class RejectedLocation
{
    /**
     * @param array $location données de la localisation telles qu'envoyées
     * @param array $measure  données de la mesure parente telles qu'envoyées (sans ses localisations)
     * @param array $errors   liste de ['propertyPath' => string, 'title' => string]
     */
    public function __construct(
        private string $uuid,
        private RegulationOrderRecord $regulationOrderRecord,
        private int $measureIndex,
        private int $locationIndex,
        private array $location,
        private array $measure,
        private array $errors,
        private \DateTimeInterface $createdAt,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getRegulationOrderRecord(): RegulationOrderRecord
    {
        return $this->regulationOrderRecord;
    }

    public function getMeasureIndex(): int
    {
        return $this->measureIndex;
    }

    public function getLocationIndex(): int
    {
        return $this->locationIndex;
    }

    public function getLocation(): array
    {
        return $this->location;
    }

    public function getMeasure(): array
    {
        return $this->measure;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
