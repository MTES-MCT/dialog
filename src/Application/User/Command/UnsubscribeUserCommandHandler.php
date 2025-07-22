<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Domain\User\Repository\UserRepositoryInterface;

final class UnsubscribeUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(UnsubscribeUserCommand $command): void
    {
        $user = $this->userRepository->findOneByEmail($command->email);

        if (null !== $user) {
            $user->setUnsubscribedEmail(true);
        }
    }
}
