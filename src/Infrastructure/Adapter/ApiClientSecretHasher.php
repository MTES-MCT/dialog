<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\ApiClientSecretHasherInterface;
use App\Infrastructure\Security\User\ApiClientUser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class ApiClientSecretHasher implements ApiClientSecretHasherInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public function hash(#[\SensitiveParameter] string $plainSecret): string
    {
        return $this->passwordHasherFactory->getPasswordHasher(ApiClientUser::class)->hash($plainSecret);
    }
}
