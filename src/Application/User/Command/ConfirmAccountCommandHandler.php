<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Domain\User\Enum\TokenTypeEnum;
use App\Domain\User\Exception\TokenExpiredException;
use App\Domain\User\Exception\TokenNotFoundException;
use App\Domain\User\Repository\TokenRepositoryInterface;
use App\Domain\User\Specification\IsTokenExpired;
use App\Domain\User\Token;

final readonly class ConfirmAccountCommandHandler
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private IsTokenExpired $isTokenExpired,
    ) {
    }

    public function __invoke(ConfirmAccountCommand $command): void
    {
        $token = $this->tokenRepository->findOneByTokenAndType(
            $command->token,
            TokenTypeEnum::CONFIRM_ACCOUNT->value,
        );

        if (!$token instanceof Token) {
            throw new TokenNotFoundException();
        }

        if ($this->isTokenExpired->isSatisfiedBy($token)) {
            throw new TokenExpiredException();
        }

        $token->getUser()->setVerified();
        $this->tokenRepository->remove($token);
    }
}
