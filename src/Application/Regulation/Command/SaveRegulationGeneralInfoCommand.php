<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Enum\RegulationSubjectEnum;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;

final class SaveRegulationGeneralInfoCommand implements CommandInterface
{
    public ?string $identifier;
    public string $source = RegulationOrderRecordSourceEnum::DIALOG->value;
    public ?string $category;
    public ?string $subject = null;
    public ?string $otherCategoryText = null;
    public ?string $title;
    public ?Organization $organization;
    public array $additionalVisas = [];
    public array $additionalReasons = [];
    public ?string $visaModelUuid = null;

    public ?string $regulationOrderTemplateUuid = null;

    public function __construct(
        public readonly ?RegulationOrderRecord $regulationOrderRecord = null,
    ) {
    }

    public static function create(
        ?RegulationOrderRecord $regulationOrderRecord = null,
        ?string $identifier = null,
    ): self {
        $regulationOrder = $regulationOrderRecord?->getRegulationOrder();
        $command = new self($regulationOrderRecord);
        $command->organization = $regulationOrderRecord?->getOrganization();
        $command->identifier = $regulationOrder?->getIdentifier() ?? $identifier;
        $command->source = $regulationOrderRecord?->getSource() ?? RegulationOrderRecordSourceEnum::DIALOG->value;
        $command->category = $regulationOrder?->getCategory();
        $command->subject = $regulationOrder?->getSubject();
        $command->otherCategoryText = $regulationOrder?->getOtherCategoryText();
        $command->title = $regulationOrder?->getTitle();
        $command->additionalVisas = $regulationOrder?->getAdditionalVisas() ?? [];
        $command->additionalReasons = $regulationOrder?->getAdditionalReasons() ?? [];
        $command->visaModelUuid = $regulationOrder?->getVisaModel()?->getUuid();
        $command->regulationOrderTemplateUuid = $regulationOrder?->getRegulationOrderTemplate()?->getUuid();

        return $command;
    }

    public function cleanOtherCategoryText(): void
    {
        if ($this->subject !== RegulationSubjectEnum::OTHER->value) {
            $this->otherCategoryText = null;
        }
    }
}
