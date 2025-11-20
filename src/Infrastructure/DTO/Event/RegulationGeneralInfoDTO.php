<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

#[Map(target: SaveRegulationGeneralInfoCommand::class)]
final class RegulationGeneralInfoDTO
{
    public ?string $identifier = null;
    public ?string $category = null;
    public ?string $subject = null;
    #[Assert\NotNull]
    public ?RegulationOrderRecordStatusEnum $status = null;
    public ?string $otherCategoryText = null;
    public ?string $title = null;
    /**
     * @var SaveMeasureDTO[]|null
     */
    public ?array $measures = null;
}
