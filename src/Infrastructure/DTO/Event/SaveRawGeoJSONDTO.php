<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Domain\Regulation\Enum\DirectionEnum;
use App\Infrastructure\Mapper\Transformers\EnumTransformers;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: SaveRawGeoJSONCommand::class)]
final class SaveRawGeoJSONDTO
{
    public ?string $label = null;
    public ?string $geometry = null;
    /**
     * Exceptions (« Sauf... ») — uniquement au niveau localisation, ignorées dans le
     * sous-objet rawGeoJSON d'une exception.
     *
     * @var SaveWholeCityExceptionDTO[]
     */
    public array $exceptions = [];
    // Sens de circulation concerné, relatif au tracé : A = premier point, B = dernier point
    #[Map(target: 'direction', transform: [EnumTransformers::class, 'toString'])]
    public ?DirectionEnum $direction = null;
}
