<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;

final class CreateOrganizationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
    }

    public function __invoke(CreateOrganizationCommand $command): Organization
    {
        $organization = (new Organization($this->idFactory->make()))
            ->setSiret($command->siret)
            ->setName($command->name);

        $this->organizationRepository->add($organization);

        return $organization;
    }
}
