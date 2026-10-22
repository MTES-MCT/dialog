<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Domain\Regulation\Enum\RoadTypeEnum;

/**
 * Exception (« Sauf... ») d'une localisation « Ville entière », « Tracé de zone » ou
 * « Tracé libre » : une voie, une route numérotée, un tracé de zone ou un tracé libre
 * exclu de la restriction. Seul le sous-objet correspondant à `roadType` est pris en
 * compte ; les exceptions imbriquées d'un sous-objet `zone` ou `rawGeoJSON` sont ignorées.
 */
final class SaveWholeCityExceptionDTO
{
    public ?RoadTypeEnum $roadType = null;
    public ?SaveNamedStreetDTO $namedStreet = null;
    public ?SaveNumberedRoadDTO $departmentalRoad = null;
    public ?SaveNumberedRoadDTO $nationalRoad = null;
    public ?SaveZoneDTO $zone = null;
    public ?SaveRawGeoJSONDTO $rawGeoJSON = null;
}
