<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserChecker implements UserCheckerInterface
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        /** @var AbstractAuthenticatedUser $user */
        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                $this->translator->trans('login.error.not_verified_account'),
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
