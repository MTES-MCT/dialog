<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Application\Regulation\Command\Location\SaveZoneCommand;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: SaveZoneCommand::class)]
final class SaveZoneDTO
{
    public ?string $label = null;
    // Périmètre dessiné (polygone GeoJSON) : les tronçons de rues couverts sont calculés côté serveur.
    public ?string $geometry = null;
}
