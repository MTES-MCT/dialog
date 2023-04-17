<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\Regulation\Exception\LocationAddressParsingException;

class LocationAddress
{
    private const ADDRESS_PATTERN = "/((?<roadName>[^\d,]+),? )?(?<postCode>\d{5}) (?<city>.+)/i";

    public function __construct(
        private string $postCode,
        private string $city,
        private string|null $roadName,
    ) {
    }

    public function getPostCode(): string
    {
        return $this->postCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getRoadName(): string|null
    {
        return $this->roadName;
    }

    /**
     * Convert a text address to a LocationAddress object.
     *
     * @throws LocationAddressParsingException: If parsing has failed.
     */
    public static function fromString(string $address): self
    {
        $matches = [];

        if (!preg_match(self::ADDRESS_PATTERN, $address, $matches, PREG_UNMATCHED_AS_NULL)) {
            $message = sprintf("Address '%s' did not have expected format '%s'", $address, self::ADDRESS_PATTERN);
            throw new LocationAddressParsingException($message);
        }

        return new LocationAddress(
            postCode: $matches['postCode'],
            city: $matches['city'],
            roadName: $matches['roadName'] ? trim($matches['roadName']) : null,
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
