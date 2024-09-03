<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\StringUtilsInterface;
use App\Domain\User\Exception\EmailAlreadyExistsException;
use App\Domain\User\Specification\IsEmailAlreadyExists;

final class SaveProfileCommandHandler
{
    public function __construct(
        private StringUtilsInterface $stringUtils,
        private IsEmailAlreadyExists $isEmailAlreadyExists,
    ) {
    }

    public function __invoke(SaveProfileCommand $command): void
    {
        $email = $this->stringUtils->normalizeEmail($command->email);
        $user = $command->user;

        // Update user

        if ($email !== $user->getEmail() && true === $this->isEmailAlreadyExists->isSatisfiedBy($email)) {
            throw new EmailAlreadyExistsException();
        }

        $user->setEmail($email);
        $user->setFullName($command->fullName);

        return;
    }
}
