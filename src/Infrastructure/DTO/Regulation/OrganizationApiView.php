<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

final readonly class OrganizationApiView
{
    public function __construct(
        public ?string $uuid,
        public string $name,
    ) {
    }
}
