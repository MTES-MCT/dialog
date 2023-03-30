<?php

declare(strict_types=1);

namespace App\Application\Organization\View;

class OrganizationListView
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $name,
    ) {
    }
}
