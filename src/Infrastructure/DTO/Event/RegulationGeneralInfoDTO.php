<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: SaveRegulationGeneralInfoCommand::class)]
final class RegulationGeneralInfoDTO
{
    public ?string $identifier = null;
    public ?string $category = null;
    public ?string $subject = null;
    public ?string $otherCategoryText = null;
    public ?string $title = null;
    public ?SaveMeasureDTO $measure = null;
}
