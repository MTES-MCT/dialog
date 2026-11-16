<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Domain\User\Enum\UserAccountStatusEnum;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\User;

final readonly class SetUserAccountStatusCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(SetUserAccountStatusCommand $command): void
    {
        if (!UserAccountStatusEnum::tryFrom($command->accountStatus)) {
            throw new \InvalidArgumentException(\sprintf('Invalid account status "%s".', $command->accountStatus));
        }

        $user = $this->userRepository->findOneByUuid($command->userUuid);
        if (!$user instanceof User) {
            throw new UserNotFoundException();
        }

        $user->setAccountStatus($command->accountStatus);
    }
}
