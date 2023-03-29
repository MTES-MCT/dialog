<?php

declare(strict_types=1);

namespace App\Application\Organization\Command;

use App\Domain\Organization\Repository\OrganizationRepositoryInterface;

class UpdateOrganizationCommandHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepositoryInterface,
    ) {
    }

    public function __invoke(UpdateOrganizationCommand $command): void
    {
        $organization = $this->organizationRepositoryInterface->findByUuid($command->organizationUuid);

        $organization->update($command->name);
    }
}
