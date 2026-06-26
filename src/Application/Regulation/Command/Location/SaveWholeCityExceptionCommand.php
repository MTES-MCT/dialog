<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\QueryInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\WholeCityException;

/**
 * Une exception à une restriction « Ville entière » : une voie (entière ou tronçon) ou un
 * tracé libre, saisi avec les MÊMES sous-formulaires qu'une localisation classique (réutilisation).
 */
final class SaveWholeCityExceptionCommand
{
    // Par défaut « Voie » pour qu'une exception fraîchement ajoutée affiche directement
    // son sous-formulaire (le prototype est rendu avec ce type pré-sélectionné).
    public ?string $roadType = RoadTypeEnum::LANE->value;
    public ?SaveNamedStreetCommand $namedStreet = null;
    public ?SaveRawGeoJSONCommand $rawGeoJSON = null;

    public function __construct(
        public readonly ?WholeCityException $exception = null,
    ) {
        if (!$exception) {
            return;
        }

        $this->roadType = $exception->getRoadType();
        $data = $exception->getData();

        if ($this->roadType === RoadTypeEnum::LANE->value) {
            $this->namedStreet = self::hydrateNamedStreet($data);
        } elseif ($this->roadType === RoadTypeEnum::RAW_GEOJSON->value) {
            $this->rawGeoJSON = self::hydrateRawGeoJSON($data, $exception->getGeometry());
        }
    }

    public function clean(): void
    {
        if ($this->roadType === RoadTypeEnum::LANE->value) {
            $this->rawGeoJSON = null;
            $this->namedStreet?->clean();
        }

        if ($this->roadType === RoadTypeEnum::RAW_GEOJSON->value) {
            $this->namedStreet = null;
        }
    }

    public function getActiveRoadCommand(): ?RoadCommandInterface
    {
        return match ($this->roadType) {
            RoadTypeEnum::LANE->value => $this->namedStreet,
            RoadTypeEnum::RAW_GEOJSON->value => $this->rawGeoJSON,
            default => null,
        };
    }

    public function getGeometryQuery(): ?QueryInterface
    {
        return $this->getActiveRoadCommand()?->getGeometryQuery();
    }

    /**
     * Une exception « voie entière » se soustrait exactement de la géométrie de la ville par
     * son identifiant BAN (voir RoadGeocoder::computeCityGeometry). Les tronçons et tracés
     * libres doivent être soustraits géométriquement.
     */
    public function getExcludedRoadBanId(): ?string
    {
        if ($this->roadType !== RoadTypeEnum::LANE->value || !$this->namedStreet) {
            return null;
        }

        return $this->namedStreet->getIsEntireStreet() ? $this->namedStreet->roadBanId : null;
    }

    public function isComplete(): bool
    {
        return match ($this->roadType) {
            RoadTypeEnum::LANE->value => !empty($this->namedStreet?->roadBanId),
            RoadTypeEnum::RAW_GEOJSON->value => !empty($this->rawGeoJSON?->geometry),
            default => false,
        };
    }

    public function getLabel(): string
    {
        if ($this->roadType === RoadTypeEnum::RAW_GEOJSON->value) {
            return (string) $this->rawGeoJSON?->label;
        }

        return (string) $this->namedStreet?->roadName;
    }

    /**
     * @return array<string, mixed>
     */
    public function toData(): array
    {
        if ($this->roadType === RoadTypeEnum::RAW_GEOJSON->value) {
            return [
                'label' => $this->rawGeoJSON?->label,
            ];
        }

        $namedStreet = $this->namedStreet;

        return [
            'cityCode' => $namedStreet?->cityCode,
            'roadBanId' => $namedStreet?->roadBanId,
            'roadName' => $namedStreet?->roadName,
            'fromPointType' => $namedStreet?->fromPointType,
            'fromHouseNumber' => $namedStreet?->fromHouseNumber,
            'fromRoadBanId' => $namedStreet?->fromRoadBanId,
            'fromRoadName' => $namedStreet?->fromRoadName,
            'toPointType' => $namedStreet?->toPointType,
            'toHouseNumber' => $namedStreet?->toHouseNumber,
            'toRoadBanId' => $namedStreet?->toRoadBanId,
            'toRoadName' => $namedStreet?->toRoadName,
            'direction' => $namedStreet?->direction,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function hydrateNamedStreet(array $data): SaveNamedStreetCommand
    {
        $command = new SaveNamedStreetCommand();
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->cityCode = $data['cityCode'] ?? null;
        $command->roadBanId = $data['roadBanId'] ?? null;
        $command->roadName = $data['roadName'] ?? null;
        $command->fromPointType = $data['fromPointType'] ?? null;
        $command->fromHouseNumber = $data['fromHouseNumber'] ?? null;
        $command->fromRoadBanId = $data['fromRoadBanId'] ?? null;
        $command->fromRoadName = $data['fromRoadName'] ?? null;
        $command->toPointType = $data['toPointType'] ?? null;
        $command->toHouseNumber = $data['toHouseNumber'] ?? null;
        $command->toRoadBanId = $data['toRoadBanId'] ?? null;
        $command->toRoadName = $data['toRoadName'] ?? null;
        $command->direction = $data['direction'] ?? \App\Domain\Regulation\Enum\DirectionEnum::BOTH->value;

        return $command;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function hydrateRawGeoJSON(array $data, ?string $geometry): SaveRawGeoJSONCommand
    {
        $command = new SaveRawGeoJSONCommand();
        $command->roadType = RoadTypeEnum::RAW_GEOJSON->value;
        $command->label = $data['label'] ?? null;
        $command->geometry = $geometry;

        return $command;
    }
}
