<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

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
        private ?RegulationOrderTemplate $regulationOrderTemplate = null,
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

    public function getRegulationOrderTemplate(): ?RegulationOrderTemplate
    {
        return $this->regulationOrderTemplate;
    }

    public function update(
        string $identifier,
        string $category,
        string $title,
        ?string $subject = null,
        ?string $otherCategoryText = null,
        ?RegulationOrderTemplate $regulationOrderTemplate = null,
    ): void {
        $this->identifier = $identifier;
        $this->category = $category;
        $this->title = $title;
        $this->subject = $subject;
        $this->otherCategoryText = $otherCategoryText;
        $this->regulationOrderTemplate = $regulationOrderTemplate;
    }
}
