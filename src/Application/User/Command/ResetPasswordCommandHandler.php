<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\PasswordHasherInterface;
use App\Domain\User\Enum\TokenTypeEnum;
use App\Domain\User\Exception\TokenExpiredException;
use App\Domain\User\Exception\TokenNotFoundException;
use App\Domain\User\Repository\TokenRepositoryInterface;
use App\Domain\User\Specification\IsTokenExpired;
use App\Domain\User\Token;

final readonly class ResetPasswordCommandHandler
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private IsTokenExpired $isTokenExpired,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(ResetPasswordCommand $command): void
    {
        $token = $this->tokenRepository->findOneByTokenAndType(
            $command->token,
            TokenTypeEnum::FORGOT_PASSWORD->value,
        );

        if (!$token instanceof Token) {
            throw new TokenNotFoundException();
        }

        if ($this->isTokenExpired->isSatisfiedBy($token)) {
            throw new TokenExpiredException();
        }

        $password = $this->passwordHasher->hash($command->password);
        $token->getUser()->getPasswordUser()->setPassword($password);
        $this->tokenRepository->remove($token);
    }
}
