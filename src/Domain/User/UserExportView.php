<?php

declare(strict_types=1);

namespace App\Domain\User;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class UserExportView
{
    public function __construct(
        #[SerializedName('Nom complet')]
        public string $fullName,
        #[SerializedName('Email')]
        public string $email,
    ) {
    }
}
