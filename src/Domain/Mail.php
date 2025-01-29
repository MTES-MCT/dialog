<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Mail
{
    public function __construct(
        public string $address,
        public string $subject,
        public string $template,
        public array $payload = [],
    ) {
    }
}
