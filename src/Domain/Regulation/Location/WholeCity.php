<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Location;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class WholeCity
{
    private Collection $exceptions;

    public function __construct(
        private string $uuid,
        private Location $location,
        private string $cityCode,
        private string $cityLabel,
    ) {
        $this->exceptions = new ArrayCollection();
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getCityCode(): string
    {
        return $this->cityCode;
    }

    public function getCityLabel(): string
    {
        return $this->cityLabel;
    }

    /**
     * @return WholeCityException[]
     */
    public function getExceptions(): array
    {
        return array_values($this->exceptions->toArray());
    }

    public function addException(WholeCityException $exception): void
    {
        if (!$this->exceptions->contains($exception)) {
            $this->exceptions->add($exception);
        }
    }

    public function removeException(WholeCityException $exception): void
    {
        $this->exceptions->removeElement($exception);
    }

    public function update(
        string $cityCode,
        string $cityLabel,
    ): void {
        $this->cityCode = $cityCode;
        $this->cityLabel = $cityLabel;
    }
}
