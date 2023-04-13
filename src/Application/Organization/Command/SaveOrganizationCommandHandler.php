<?php

declare(strict_types=1);

namespace App\Application\Organization\Command;

use App\Application\IdFactoryInterface;
use App\Domain\Organization\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Organization;

class SaveOrganizationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private OrganizationRepositoryInterface $OrganizationRepository,
    ) {
    }

    public function __invoke(SaveOrganizationCommand $command): void
    {
        if ($command->organization) {
            $command->organization->update($command->name);
        } else {
            $this->OrganizationRepository->save(new Organization(
                uuid: $this->idFactory->make(),
                name: $command->name,
            ));
        }
    }
}
