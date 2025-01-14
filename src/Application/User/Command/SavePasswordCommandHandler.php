<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\PasswordHasherInterface;

final class SavePasswordCommandHandler
{
    public function __construct(
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(SavePasswordCommand $command): void
    {
        $user = $command->user;
        $password = $this->passwordHasher->hash($command->password);
        $user->getPasswordUser()->setPassword($password);
    }
}
