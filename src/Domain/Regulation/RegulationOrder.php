<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\Organization\VisaModel\VisaModel;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class RegulationOrder
{
    private Collection $measures;
    private ?RegulationOrderRecord $regulationOrderRecord = null;

    public function __construct(
        private string $uuid,
        private string $identifier,
        private string $category,
        private string $title,
        private ?string $subject = null,
        private ?string $otherCategoryText = null,
        private ?VisaModel $visaModel = null,
        private ?array $additionalVisas = [],
        private ?array $additionalReasons = [],
    ) {
        $this->measures = new ArrayCollection();
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getOtherCategoryText(): ?string
    {
        return $this->otherCategoryText;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getMeasures(): iterable
    {
        return $this->measures;
    }

    public function addMeasure(Measure $measure): void
    {
        if (!$this->measures->contains($measure)) {
            $this->measures->add($measure);
        }
    }

    public function getRegulationOrderRecord(): ?RegulationOrderRecord
    {
        return $this->regulationOrderRecord;
    }

    public function isPermanent(): bool
    {
        return $this->category === RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
    }

    public function getVisaModel(): ?VisaModel
    {
        return $this->visaModel;
    }

    public function getAdditionalVisas(): array
    {
        return $this->additionalVisas ?? [];
    }

    public function getAdditionalReasons(): array
    {
        return $this->additionalReasons ?? [];
    }

    public function update(
        string $identifier,
        string $category,
        string $title,
        ?string $subject = null,
        ?string $otherCategoryText = null,
        array $additionalVisas = [],
        array $additionalReasons = [],
        ?VisaModel $visaModel = null,
    ): void {
        $this->identifier = $identifier;
        $this->category = $category;
        $this->title = $title;
        $this->subject = $subject;
        $this->otherCategoryText = $otherCategoryText;
        $this->additionalVisas = $additionalVisas;
        $this->additionalReasons = $additionalReasons;
        $this->visaModel = $visaModel;
    }
}
