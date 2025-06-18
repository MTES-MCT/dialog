<?php

declare(strict_types=1);

namespace App\Application\Organization\SigningAuthority\Command;

use App\Application\IdFactoryInterface;
use App\Domain\Organization\SigningAuthority\Repository\SigningAuthorityRepositoryInterface;
use App\Domain\Organization\SigningAuthority\SigningAuthority;

final class SaveSigningAuthorityCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private SigningAuthorityRepositoryInterface $signingAuthorityRepository,
    ) {
    }

    public function __invoke(SaveSigningAuthorityCommand $command): SigningAuthority
    {
        if ($signingAuthority = $command->signingAuthority) {
            $signingAuthority->update(
                name: $command->name,
                role: $command->role,
                signatoryName: $command->signatoryName,
            );

            return $signingAuthority;
        }

        return $this->signingAuthorityRepository->add(
            new SigningAuthority(
                uuid: $this->idFactory->make(),
                name: $command->name,
                role: $command->role,
                signatoryName: $command->signatoryName,
                organization: $command->organization,
            ),
        );
    }
}
