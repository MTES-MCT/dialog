<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;

final class SaveRegulationGeneralInfoCommand implements CommandInterface
{
    public ?string $identifier;
    public string $source = RegulationOrderRecordSourceEnum::DIALOG->value;
    public ?string $category;
    public ?string $object;
    public ?string $otherObjectText = null;
    public ?string $description;
    public ?Organization $organization;
    public array $additionalVisas = [];
    public array $additionalReasons = [];
    public ?string $visaModelUuid = null;

    public function __construct(
        public readonly ?RegulationOrderRecord $regulationOrderRecord = null,
    ) {
    }

    public static function create(
        ?RegulationOrderRecord $regulationOrderRecord = null,
        ?\DateTimeImmutable $startDate = null,
    ): self {
        $regulationOrder = $regulationOrderRecord?->getRegulationOrder();
        $command = new self($regulationOrderRecord);
        $command->organization = $regulationOrderRecord?->getOrganization();
        $command->identifier = $regulationOrder?->getIdentifier();
        $command->source = $regulationOrderRecord?->getSource() ?? RegulationOrderRecordSourceEnum::DIALOG->value;
        $command->category = $regulationOrder?->getCategory();
        $command->object = $regulationOrder?->getObject();
        $command->otherObjectText = $regulationOrder?->getOtherObjectText();
        $command->description = $regulationOrder?->getDescription();
        $command->additionalVisas = $regulationOrder?->getAdditionalVisas() ?? [];
        $command->additionalReasons = $regulationOrder?->getAdditionalReasons() ?? [];
        $command->visaModelUuid = $regulationOrder?->getVisaModel()?->getUuid();

        return $command;
    }

    public function cleanOtherObjectText(): void
    {
        if ($this->category !== RegulationOrderCategoryEnum::OTHER->value) {
            $this->otherObjectText = null;
        }
    }
}
