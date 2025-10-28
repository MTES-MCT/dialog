<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Application\Regulation\Command\Location\SaveNumberedRoadCommand;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Infrastructure\Mapper\Transformers\EnumTransformers;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: SaveNumberedRoadCommand::class)]
final class SaveNumberedRoadDTO
{
    public ?string $administrator = null;
    public ?string $roadNumber = null;
    public ?string $fromDepartmentCode = null;
    public ?string $fromPointNumber = null;
    public ?int $fromAbscissa = null;
    public ?string $fromSide = null;
    public ?string $toDepartmentCode = null;
    public ?string $toPointNumber = null;
    public ?int $toAbscissa = null;
    public ?string $toSide = null;
    #[Map(target: 'direction', transform: [EnumTransformers::class, 'toString'])]
    public ?DirectionEnum $direction = null;
    public ?string $geometry = null;
    public ?string $storageAreaUuid = null;
}
