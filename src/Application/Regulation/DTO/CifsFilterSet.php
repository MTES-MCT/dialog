<?php

declare(strict_types=1);

namespace App\Application\Regulation\DTO;

final readonly class CifsFilterSet
{
    public function __construct(
        public array $allowedSources = [],
        public array $excludedIdentifiers = [],
        public array $allowedLocationIds = [],
    ) {
    }

    public static function fromJSON(array $data): self
    {
        return new self(
            allowedSources: $data['allowed_sources'] ?? [],
            excludedIdentifiers: $data['excluded_identifiers'] ?? [],
            allowedLocationIds: $data['allowed_location_ids'] ?? [],
        );
    }
}
