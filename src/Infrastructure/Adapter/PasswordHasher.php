<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\PasswordHasherInterface;
use App\Infrastructure\Security\User\PasswordUser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class PasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $encoderFactory,
    ) {
    }

    public function hash(#[\SensitiveParameter] string $password): string
    {
        return $this->encoderFactory->getPasswordHasher(PasswordUser::class)->hash($password);
    }
}
