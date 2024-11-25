<?php

declare(strict_types=1);

namespace App\Application\Organization\Logo\Command;

use App\Application\StorageInterface;

final class DeleteOrganizationLogoCommandHandler
{
    public function __construct(
        private StorageInterface $storage,
    ) {
    }

    public function __invoke(DeleteOrganizationLogoCommand $command): void
    {
        $organization = $command->organization;

        if (!$logo = $organization->getLogo()) {
            return;
        }

        $this->storage->delete($logo);
        $organization->setLogo(null);
    }
}
