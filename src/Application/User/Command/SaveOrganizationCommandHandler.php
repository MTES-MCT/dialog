<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\IdFactoryInterface;
use App\Application\StorageInterface;
use App\Domain\Organization\Establishment\Establishment;
use App\Domain\Organization\Establishment\Repository\EstablishmentRepositoryInterface;
use App\Domain\User\Organization;

final class SaveOrganizationCommandHandler
{
    public function __construct(
        private StorageInterface $storage,
        private IdFactoryInterface $idFactory,
        private EstablishmentRepositoryInterface $establishmentRepository,
    ) {
    }

    public function __invoke(SaveOrganizationCommand $command): Organization
    {
        $organization = $command->organization;

        if ($command->file) {
            $folder = \sprintf('organizations/%s', $organization->getUuid());
            if ($logo = $organization->getLogo()) {
                $this->storage->delete($logo);
            }

            $organization->setLogo($this->storage->write($folder, $command->file));
        }

        $organization->update($command->name);

        if ($establishment = $organization->getEstablishment()) {
            $establishment->update(
                address: $command->address,
                zipCode: $command->zipCode,
                city: $command->city,
                addressComplement: $command->addressComplement,
            );
        } else {
            $establishment = new Establishment(
                uuid: $this->idFactory->make(),
                address: $command->address,
                zipCode: $command->zipCode,
                city: $command->city,
                organization: $organization,
                addressComplement: $command->addressComplement,
            );
            $this->establishmentRepository->add($establishment);
            $organization->setEstablishment($establishment);
        }

        return $organization;
    }
}
