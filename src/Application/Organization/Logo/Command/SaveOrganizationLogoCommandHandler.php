<?php

declare(strict_types=1);

namespace App\Application\Organization\Logo\Command;

use App\Application\StorageInterface;

final class SaveOrganizationLogoCommandHandler
{
    public function __construct(
        private StorageInterface $storage,
    ) {
    }

    public function __invoke(SaveOrganizationLogoCommand $command): void
    {
        $folder = \sprintf('organizations/%s', $command->organization->getUuid());
        $organization = $command->organization;

        if ($logo = $organization->getLogo()) {
            $this->storage->delete($logo);
        }

        $organization->setLogo($this->storage->write($folder, 'logo', $command->file));
    }
}
