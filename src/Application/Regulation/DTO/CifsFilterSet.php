<?php

declare(strict_types=1);

namespace App\Application\Regulation\DTO;

final readonly class CifsFilterSet
{
    public function __construct(
        public array $allowedSources = [],
        public array $excludedIdentifiers = [],
        public array $allowedLocationIds = [],
        public array $excludedOrgUuids = [],
    ) {
    }
}
