<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Infrastructure\Mapper\Transformers\EnumTransformers;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: SaveLocationCommand::class)]
final class SaveLocationDTO
{
    #[Map(target: 'roadType', transform: [EnumTransformers::class, 'toString'])]
    public ?RoadTypeEnum $roadType = null;
    public ?SaveNamedStreetDTO $namedStreet = null;
    public ?SaveNumberedRoadDTO $departmentalRoad = null;
    public ?SaveNumberedRoadDTO $nationalRoad = null;
    public ?SaveRawGeoJSONDTO $rawGeoJSON = null;
}
