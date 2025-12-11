<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Infrastructure\Mapper\Transformers\DateTimeTransformers;
use App\Infrastructure\Mapper\Transformers\EnumTransformers;
use App\Infrastructure\Mapper\Transformers\LocationsTransformer;
use App\Infrastructure\Mapper\Transformers\PeriodsTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: SaveMeasureCommand::class)]
final class SaveMeasureDTO
{
    #[Map(target: 'type', transform: [EnumTransformers::class, 'toString'])]
    public ?MeasureTypeEnum $type = null;
    public ?int $maxSpeed = null;
    #[Map(target: 'createdAt', transform: [DateTimeTransformers::class, 'fromIso'])]
    public ?string $createdAt = null;
    /** @var SavePeriodDTO[] */
    #[Map(target: 'periods', transform: [PeriodsTransformer::class, 'toCommands'])]
    public array $periods = [];
    /** @var SaveLocationDTO[] */
    #[Map(target: 'locations', transform: [LocationsTransformer::class, 'toCommands'])]
    public array $locations = [];
    public ?SaveVehicleSetDTO $vehicleSet = null;
}
