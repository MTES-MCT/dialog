<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Domain\User\Repository\UserRepositoryInterface;

class DeleteUserCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepositoryInterface,
    ) {
    }

        public function __invoke(DeleteUserCommand $command): void
        {
            $user = $command->user;
            $this->userRepositoryInterface->delete($user);
        }
}
