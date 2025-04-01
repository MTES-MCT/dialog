<?php

declare(strict_types=1);

namespace App\Application\User\View;

final readonly class OrganizationView
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $siret,
    ) {
    }
}
