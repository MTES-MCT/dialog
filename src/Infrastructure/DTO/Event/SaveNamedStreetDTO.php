<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Infrastructure\Mapper\Transformers\EnumTransformers;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: SaveNamedStreetCommand::class)]
final class SaveNamedStreetDTO
{
    public ?string $cityCode = null;
    public ?string $cityLabel = null;
    public ?string $roadName = null;
    public ?string $fromPointType = null;
    public ?string $fromHouseNumber = null;
    public ?string $fromRoadName = null;
    public ?string $toPointType = null;
    public ?string $toHouseNumber = null;
    public ?string $toRoadName = null;
    public ?string $geometry = null;
    #[Map(target: 'direction', transform: [EnumTransformers::class, 'toString'])]
    public ?DirectionEnum $direction = null;
}
