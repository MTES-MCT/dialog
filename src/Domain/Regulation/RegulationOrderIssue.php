<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\User\Organization;

class RegulationOrderIssue
{
    public function __construct(
        private string $uuid,
        private string $identifier,
        private Organization $organization,
        private string $level,
        private string $source,
        private string $context,
        private \DateTimeInterface $createdAt,
        private ?string $geometry = null,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getGeometry(): ?string
    {
        return $this->geometry;
    }
}
