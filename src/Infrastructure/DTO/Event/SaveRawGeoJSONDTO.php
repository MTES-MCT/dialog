<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: SaveRawGeoJSONCommand::class)]
final class SaveRawGeoJSONDTO
{
    public ?string $label = null;
    public ?string $geometry = null;
}
