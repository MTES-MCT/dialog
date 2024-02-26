<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\CommandInterface;
use App\Domain\User\Organization;

final class CreateOrganizationCommand implements CommandInterface
{
    public ?string $siret;
    public ?string $name;

    public function __construct(
        public readonly ?Organization $organization = null,
    ) {
        $this->siret = $organization?->getSiret();
        $this->name = $organization?->getName();
    }
}
