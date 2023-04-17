<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\Regulation\Exception\LocationAddressParsingException;

class LocationAddress
{
    private const ADDRESS_PATTERN = "/(?<roadName>[^\d,]+),? (?<postCode>\d{5}) (?<city>\D+)/i";

    public function __construct(
        private string $postCode,
        private string $city,
        private string $roadName,
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

    public function getRoadName(): string
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

        if (!preg_match(self::ADDRESS_PATTERN, $address, $matches)) {
            $message = sprintf("Address '%s' did not have expected format '%s'", $address, self::ADDRESS_PATTERN);
            throw new LocationAddressParsingException($message);
        }

        return new LocationAddress(
            postCode: $matches['postCode'],
            city: $matches['city'],
            roadName: trim($matches['roadName']),
        );
    }
}
