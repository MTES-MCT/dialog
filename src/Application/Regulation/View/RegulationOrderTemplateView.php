<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final readonly class RegulationOrderTemplateView
{
    public function __construct(
        public string $uuid,
        public string $name,
        public ?string $organizationUuid,
    ) {
    }
}
