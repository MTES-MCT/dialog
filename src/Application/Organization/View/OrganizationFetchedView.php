<?php

declare(strict_types=1);

namespace App\Application\Organization\View;

final readonly class OrganizationFetchedView
{
    public function __construct(
        public string $name,
        public ?string $code = null,
        public ?string $codeType = null,
        public ?string $geometry = null,
    ) {
    }
}
