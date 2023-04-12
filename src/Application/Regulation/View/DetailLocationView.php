<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

class DetailLocationView
{
    private const ADDRESS_PATTERN = "/(?<roadName>[^\d,]+),? (?<postCode>\d{5}) (?<city>\D+)/i";

    private array $parsedAddress;

    public function __construct(
        public readonly string $address,
        public readonly ?string $fromHouseNumber,
        public readonly ?string $toHouseNumber,
    ) {
        $pattern = self::ADDRESS_PATTERN;
        if (!preg_match($pattern, $address, $matches)) {
            throw new \Exception("Address '$address' did not have expected format '$pattern'");
        }
        $this->parsedAddress = $matches;
    }

    // TODO tests

    public function getCity(): string
    {
        return $this->parsedAddress['city'];
    }

    public function getPostCode(): string
    {
        return $this->parsedAddress['postCode'];
    }

    public function getRoadName(): string
    {
        return trim($this->parsedAddress['roadName']);
    }
}
