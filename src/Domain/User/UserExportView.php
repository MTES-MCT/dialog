<?php

declare(strict_types=1);

namespace App\Domain\User;

final readonly class UserExportView
{
    public string $registrationDate;
    public ?string $lastActiveAt;

    public function __construct(
        public string $fullName,
        public string $email,
        \DateTimeInterface $registrationDate,
        ?\DateTimeInterface $lastActiveAt,
        public string $organizationName,
    ) {
        $this->registrationDate = $registrationDate->format(\DateTimeImmutable::ATOM);
        $this->lastActiveAt = $lastActiveAt?->format(\DateTimeImmutable::ATOM);
    }
}
