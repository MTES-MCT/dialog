<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\QueryInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\WholeCityException;

/**
 * Une exception à une restriction « Ville entière », « Tracé de zone » ou « Tracé libre » :
 * une voie (entière ou tronçon), une route départementale ou nationale, un tracé de zone ou
 * un tracé libre, saisi avec les MÊMES sous-formulaires qu'une localisation classique
 * (réutilisation). Seule « Ville entière » n'est pas proposée : une exception ne peut pas
 * être elle-même une ville entière.
 */
final class SaveWholeCityExceptionCommand
{
    // Par défaut « Voie » pour qu'une exception fraîchement ajoutée affiche directement
    // son sous-formulaire (le prototype est rendu avec ce type pré-sélectionné).
    public ?string $roadType = RoadTypeEnum::LANE->value;
    public ?SaveNamedStreetCommand $namedStreet = null;
    public ?SaveNumberedRoadCommand $departmentalRoad = null;
    public ?SaveNumberedRoadCommand $nationalRoad = null;
    public ?SaveZoneCommand $zone = null;
    public ?SaveRawGeoJSONCommand $rawGeoJSON = null;

    // Mémo intra-requête : la géométrie calculée par la requête de géométrie parente
    // (soustraction) est réutilisée à l'enregistrement de l'exception, pour ne géocoder
    // qu'une seule fois. Ne fait pas partie des données persistées ni de la signature.
    public ?string $computedGeometry = null;

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
        } elseif ($this->roadType === RoadTypeEnum::DEPARTMENTAL_ROAD->value) {
            $this->departmentalRoad = SaveNumberedRoadCommand::fromData($data, $this->roadType);
        } elseif ($this->roadType === RoadTypeEnum::NATIONAL_ROAD->value) {
            $this->nationalRoad = SaveNumberedRoadCommand::fromData($data, $this->roadType);
        } elseif ($this->roadType === RoadTypeEnum::ZONE->value) {
            $this->zone = self::hydrateZone($data);
        } elseif ($this->roadType === RoadTypeEnum::RAW_GEOJSON->value) {
            $this->rawGeoJSON = self::hydrateRawGeoJSON($data, $exception->getGeometry());
        }
    }

    public function clean(): void
    {
        $active = $this->getActiveRoadCommand();

        // On ne conserve que le sous-formulaire du type sélectionné, et on le nettoie
        // (décodage des points de repère saisis, notamment).
        foreach (['namedStreet', 'departmentalRoad', 'nationalRoad', 'zone', 'rawGeoJSON'] as $field) {
            if ($this->$field !== $active) {
                $this->$field = null;
            }
        }

        $active?->clean();
    }

    public function getActiveRoadCommand(): ?RoadCommandInterface
    {
        return match ($this->roadType) {
            RoadTypeEnum::LANE->value => $this->namedStreet,
            RoadTypeEnum::DEPARTMENTAL_ROAD->value => $this->departmentalRoad,
            RoadTypeEnum::NATIONAL_ROAD->value => $this->nationalRoad,
            RoadTypeEnum::ZONE->value => $this->zone,
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
     * son identifiant BAN (voir RoadGeocoder::computeCityGeometry). Les tronçons, routes
     * numérotées, tracés de zone et tracés libres doivent être soustraits géométriquement.
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
            RoadTypeEnum::LANE->value => self::isFilled($this->namedStreet?->roadBanId),
            RoadTypeEnum::DEPARTMENTAL_ROAD->value => self::isFilled($this->departmentalRoad?->roadNumber),
            RoadTypeEnum::NATIONAL_ROAD->value => self::isFilled($this->nationalRoad?->roadNumber),
            RoadTypeEnum::ZONE->value => self::isFilled($this->zone?->geometry),
            RoadTypeEnum::RAW_GEOJSON->value => self::isFilled($this->rawGeoJSON?->geometry),
            default => false,
        };
    }

    // « 0 » est une valeur renseignée : pas de test de vérité PHP (empty('0') vaut true).
    private static function isFilled(?string $value): bool
    {
        return $value !== null && $value !== '';
    }

    public function getLabel(): string
    {
        return match ($this->roadType) {
            RoadTypeEnum::DEPARTMENTAL_ROAD->value => (string) $this->departmentalRoad?->roadNumber,
            RoadTypeEnum::NATIONAL_ROAD->value => (string) $this->nationalRoad?->roadNumber,
            RoadTypeEnum::ZONE->value => (string) $this->zone?->label,
            RoadTypeEnum::RAW_GEOJSON->value => (string) $this->rawGeoJSON?->label,
            default => (string) $this->namedStreet?->roadName,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toData(): array
    {
        if ($this->roadType === RoadTypeEnum::DEPARTMENTAL_ROAD->value) {
            return $this->departmentalRoad?->toData() ?? [];
        }

        if ($this->roadType === RoadTypeEnum::NATIONAL_ROAD->value) {
            return $this->nationalRoad?->toData() ?? [];
        }

        if ($this->roadType === RoadTypeEnum::ZONE->value) {
            return [
                'label' => $this->zone?->label,
                // Périmètre dessiné, conservé pour ré-édition (la géométrie de l'exception
                // stockée par ailleurs contient les tronçons de rues couverts).
                'geometry' => $this->zone?->geometry,
            ];
        }

        if ($this->roadType === RoadTypeEnum::RAW_GEOJSON->value) {
            return [
                'label' => $this->rawGeoJSON?->label,
            ];
        }

        $namedStreet = $this->namedStreet;

        return [
            'cityCode' => $namedStreet?->cityCode,
            'cityLabel' => $namedStreet?->cityLabel,
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
        $command->cityLabel = $data['cityLabel'] ?? null;
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
    private static function hydrateZone(array $data): SaveZoneCommand
    {
        $command = new SaveZoneCommand();
        $command->roadType = RoadTypeEnum::ZONE->value;
        $command->label = $data['label'] ?? null;
        $command->geometry = $data['geometry'] ?? null;

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
