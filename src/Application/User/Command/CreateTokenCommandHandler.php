<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Repository\TokenRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Token;
use App\Domain\User\TokenGenerator;
use App\Domain\User\User;

final readonly class CreateTokenCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private UserRepositoryInterface $userRepository,
        private TokenRepositoryInterface $tokenRepository,
        private DateUtilsInterface $dateUtils,
        private TokenGenerator $tokenGenerator,
    ) {
    }

    public function __invoke(CreateTokenCommand $command): Token
    {
        $user = $this->userRepository->findOneByEmail($command->email);
        if (!$user instanceof User || !$user->getPasswordUser()) {
            throw new UserNotFoundException();
        }

        $expirationDate = $this->dateUtils->getNow()->modify('+1 day');
        $token = $this->tokenGenerator->generate();

        return $this->tokenRepository->add(
            new Token(
                uuid: $this->idFactory->make(),
                token: $token,
                type: $command->type,
                user: $user,
                expirationDate: $expirationDate,
            ),
        );
    }
}
