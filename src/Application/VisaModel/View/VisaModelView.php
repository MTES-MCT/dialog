<?php

declare(strict_types=1);

namespace App\Application\VisaModel\View;

final readonly class VisaModelView
{
    public function __construct(
        public string $uuid,
        public string $name,
        public ?string $description,
        public ?string $organizationUuid,
        public ?string $organizationName,
    ) {
    }
}
