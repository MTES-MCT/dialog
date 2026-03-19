<?php

declare(strict_types=1);

namespace App\Application\Organization\ApiClient\View;

final readonly class ApiClientCreatedView
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
    ) {
    }
}
