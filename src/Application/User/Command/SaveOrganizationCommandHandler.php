<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Domain\User\Exception\SiretAlreadyRequestedException;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;

final class SaveOrganizationCommandHandler
{
    public function __construct(
        private IdFactoryInterface $idFactory,
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
    }

    public function __invoke(SaveOrganizationCommand $command): Organization
    {
        if (!$command->organization) {
            $organization = (new Organization($this->idFactory->make()))
                ->setSiret($command->siret)
                ->setName($command->name);

            $this->organizationRepository->add($organization);

            return $organization;
        }

        if ($command->siret !== $command->organization->getSiret()
            && $this->organizationRepository->findOneBySiret($command->siret) instanceof Organization) {
            throw new SiretAlreadyRequestedException();
        }

        $command->organization->update($command->name, $command->siret);

        return $command->organization;
    }
}
