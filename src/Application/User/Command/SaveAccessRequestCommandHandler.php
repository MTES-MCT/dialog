<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Application\PasswordHasherInterface;
use App\Domain\User\AccessRequest;
use App\Domain\User\Exception\AccessAlreadyRequestedException;
use App\Domain\User\Repository\AccessRequestRepositoryInterface;
use App\Domain\User\Specification\IsAccessAlreadyRequested;

final class SaveAccessRequestCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private AccessRequestRepositoryInterface $feedbackRepository,
        private IsAccessAlreadyRequested $isAccessAlreadyRequested,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(SaveAccessRequestCommand $command): void
    {
        $email = trim(strtolower($command->email));

        if (true === $this->isAccessAlreadyRequested->isSatisfiedBy($email)) {
            throw new AccessAlreadyRequestedException();
        }

        $this->feedbackRepository->add(
            new AccessRequest(
                uuid: $this->idFactory->make(),
                fullName: $command->fullName,
                email: $email,
                password: $this->passwordHasher->hash($command->password),
                organization: $command->organization,
                siret: $command->siret,
                comment: $command->comment,
                consentToBeContacted: $command->consentToBeContacted,
            ),
        );
    }
}
