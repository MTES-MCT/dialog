<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Domain\User\Repository\UserRepositoryInterface;

final class DeleteUserCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $command->user;
        $this->userRepository->remove($user);
    }
}
