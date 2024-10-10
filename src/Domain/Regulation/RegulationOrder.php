<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\VisaModel\VisaModel;
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
        private string $description,
        private ?\DateTimeInterface $startDate,
        private ?\DateTimeInterface $endDate = null,
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
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
        return !$this->endDate;
    }

    public function getVisaModel(): ?VisaModel
    {
        return $this->visaModel;
    }

    public function getAdditionalVisas(): ?array
    {
        return $this->additionalVisas;
    }

    public function getAdditionalReasons(): ?array
    {
        return $this->additionalReasons;
    }

    public function update(
        string $identifier,
        string $category,
        string $description,
        \DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate = null,
        ?string $otherCategoryText = null,
        array $additionalVisas = [],
        array $additionalReasons = [],
    ): void {
        $this->identifier = $identifier;
        $this->category = $category;
        $this->description = $description;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->otherCategoryText = $otherCategoryText;
        $this->additionalVisas = $additionalVisas;
        $this->additionalReasons = $additionalReasons;
    }
}
