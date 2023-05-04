<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

class LocationAddress
{
    private const ADDRESS_PATTERN = "/((?<roadName>[^\d,]+),? )?(?<postCode>\d{5}) (?<city>.+)/i";

    public function __construct(
        private ?string $postCode = null,
        private ?string $city = null,
        private ?string $roadName = null,
    ) {
    }

    public function getPostCode(): ?string
    {
        return $this->postCode;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getRoadName(): ?string
    {
        return $this->roadName;
    }

    /**
     * Convert a text address to a LocationAddress object.
     */
    public static function fromString(string $address): self
    {
        $matches = [];

        // If parsing has failed
        if (!preg_match(self::ADDRESS_PATTERN, $address, $matches)) {
            return new LocationAddress(null, null, $address);
        }

        return new LocationAddress(
            postCode: $matches['postCode'],
            city: $matches['city'],
            roadName: empty($matches['roadName']) ? null : trim($matches['roadName']),
        );
    }

    public function __toString(): string
    {
        if ($this->roadName) {
            return sprintf('%s, %s %s', $this->roadName, $this->postCode, $this->city);
        } else {
            return sprintf('%s %s', $this->postCode, $this->city);
        }
    }
}
