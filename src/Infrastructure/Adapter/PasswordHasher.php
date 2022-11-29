<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\PasswordHasherInterface;
use App\Infrastructure\Security\SymfonyUser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class PasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $encoderFactory,
    ) {
    }

    public function hash(string $password): string
    {
        return $this->encoderFactory->getPasswordHasher(SymfonyUser::class)->hash($password);
    }
}
