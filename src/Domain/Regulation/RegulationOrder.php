<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

class RegulationOrder
{
    private iterable $locations = [];

    public function __construct(
        private string $uuid,
        private string $identifier,
        private string $description,
        private ?string $email = null,
        private ?\DateTimeInterface $startDate,
        private ?\DateTimeInterface $endDate = null,
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getEmail(): string
    {
        return $this->email;
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

    public function update(
        string $identifier,
        string $description,
        string $email,
        \DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate = null,
    ): void {
        $this->identifier = $identifier;
        $this->description = $description;
        $this->email = $email;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
}
