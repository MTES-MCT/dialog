<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

class Location
{
    private iterable $measures = [];

    public function __construct(
        private string $uuid,
        private RegulationOrder $regulationOrder,
        private string $address,
        private ?string $fromHouseNumber,
        private ?string $fromPoint,
        private ?string $toHouseNumber,
        private ?string $toPoint,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getRegulationOrder(): RegulationOrder
    {
        return $this->regulationOrder;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getFromHouseNumber(): ?string
    {
        return $this->fromHouseNumber;
    }

    public function getFromPoint(): ?string
    {
        return $this->fromPoint;
    }

    public function getToHouseNumber(): ?string
    {
        return $this->toHouseNumber;
    }

    public function getToPoint(): ?string
    {
        return $this->toPoint;
    }

    public function getMeasures(): iterable
    {
        return $this->measures;
    }

    public function update(
        string $address,
        ?string $fromHouseNumber,
        ?string $fromPoint,
        ?string $toHouseNumber,
        ?string $toPoint,
        ?array $measures,
    ): void {
        $this->address = $address;
        $this->fromHouseNumber = $fromHouseNumber;
        $this->fromPoint = $fromPoint;
        $this->toHouseNumber = $toHouseNumber;
        $this->toPoint = $toPoint;
        $this->measures = $measures;
    }
}
