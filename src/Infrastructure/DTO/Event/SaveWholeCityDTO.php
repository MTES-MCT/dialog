<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Application\Regulation\Command\Location\SaveWholeCityCommand;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: SaveWholeCityCommand::class)]
final class SaveWholeCityDTO
{
    public ?string $cityCode = null;
    public ?string $cityLabel = null;
    /** @var SaveWholeCityExceptionDTO[] */
    public array $exceptions = [];
}
