<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\User\Organization;
use App\Domain\User\OrganizationRegulationAccessInterface;

class RegulationOrderRecord implements OrganizationRegulationAccessInterface, RegulationLocationsInterface, RegulationMeasuresInterface
{
    public function __construct(
        private string $uuid,
        private string $source,
        private string $status,
        private RegulationOrder $regulationOrder,
        private \DateTimeInterface $createdAt,
        private Organization $organization,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function getOrganizationUuid(): ?string
    {
        return $this->organization->getUuid();
    }

    public function getOrganizationName(): ?string
    {
        return $this->organization->getName();
    }

    public function getRegulationOrder(): RegulationOrder
    {
        return $this->regulationOrder;
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;
    }

    public function updateOrganization(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function countLocations(): int
    {
        return \count($this->regulationOrder->getLocations());
    }

    public function getLocationUuids(): array
    {
        $uuids = [];

        foreach ($this->regulationOrder->getLocations() as $location) {
            $uuids[] = $location->getUuid();
        }

        return $uuids;
    }

    public function countMeasures(): int
    {
        return \count($this->regulationOrder->getMeasures());
    }

    public function getMeasureUuids(): array
    {
        $uuids = [];

        foreach ($this->regulationOrder->getMeasures() as $measure) {
            $uuids[] = $measure->getUuid();
        }

        return $uuids;
    }
}
