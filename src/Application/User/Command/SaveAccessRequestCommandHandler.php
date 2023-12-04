<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Application\PasswordHasherInterface;
use App\Application\StringUtilsInterface;
use App\Domain\User\AccessRequest;
use App\Domain\User\Exception\AccessAlreadyRequestedException;
use App\Domain\User\Repository\AccessRequestRepositoryInterface;
use App\Domain\User\Specification\IsAccessAlreadyRequested;

final class SaveAccessRequestCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private AccessRequestRepositoryInterface $accessRequestRepository,
        private IsAccessAlreadyRequested $isAccessAlreadyRequested,
        private PasswordHasherInterface $passwordHasher,
        private StringUtilsInterface $stringUtils,
    ) {
    }

    public function __invoke(SaveAccessRequestCommand $command): void
    {
        $email = $this->stringUtils->normalizeEmail($command->email);

        if (true === $this->isAccessAlreadyRequested->isSatisfiedBy($email)) {
            throw new AccessAlreadyRequestedException();
        }

        $this->accessRequestRepository->add(
            new AccessRequest(
                uuid: $this->idFactory->make(),
                fullName: $command->fullName,
                email: $email,
                password: $this->passwordHasher->hash($command->password),
                organization: $command->organizationName,
                comment: $command->comment,
                siret: $command->organizationSiret,
                consentToBeContacted: $command->consentToBeContacted,
            ),
        );
    }
}
