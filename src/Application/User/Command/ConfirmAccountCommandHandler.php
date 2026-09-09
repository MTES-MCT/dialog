<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Domain\User\Enum\TokenTypeEnum;
use App\Domain\User\Exception\TokenAlreadyUsedException;
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
        private DateUtilsInterface $dateUtils,
    ) {
    }

    public function __invoke(ConfirmAccountCommand $command): string
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

        if ($token->isUsed()) {
            throw new TokenAlreadyUsedException();
        }

        $token->getUser()->setIsVerified();
        $email = $token->getUser()->getEmail();
        // Le token est conservé jusqu'à sa purge afin de garder une trace des demandes (#2068).
        $token->markAsUsed($this->dateUtils->getNow());

        return $email;
    }
}
