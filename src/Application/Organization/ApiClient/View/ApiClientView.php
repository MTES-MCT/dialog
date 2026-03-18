<?php

declare(strict_types=1);

namespace App\Application\Organization\ApiClient\View;

final readonly class ApiClientView
{
    public function __construct(
        public string $uuid,
        public string $clientId,
        public ?string $userFullName,
        public ?string $userEmail,
        public bool $isOwner,
        public \DateTimeInterface $createdAt,
        public ?\DateTimeInterface $lastUsedAt,
        public bool $isActive,
    ) {
    }
}
