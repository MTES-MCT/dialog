<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class RegulationOrder
{
    private Collection $locations;
    private RegulationOrderRecord $regulationOrderRecord;

    public function __construct(
        private string $uuid,
        private string $identifier,
        private string $category,
        private string $description,
        private ?\DateTimeInterface $startDate,
        private ?\DateTimeInterface $endDate = null,
        private ?string $otherCategoryText = null,
    ) {
        $this->locations = new ArrayCollection();
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

    public function getLocations(): iterable
    {
        return $this->locations;
    }

    public function addLocation(Location $location): void
    {
        if ($this->locations->contains($location)) {
            return;
        }

        $this->locations->add($location);
    }

    public function getRegulationOrderRecord(): RegulationOrderRecord
    {
        return $this->regulationOrderRecord;
    }

    public function update(
        string $identifier,
        string $category,
        string $description,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate = null,
        string $otherCategoryText = null,
    ): void {
        $this->identifier = $identifier;
        $this->category = $category;
        $this->description = $description;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->otherCategoryText = $otherCategoryText;
    }
}
