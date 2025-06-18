<?php

declare(strict_types=1);

namespace App\Application\Organization\SigningAuthority\Command;

use App\Application\CommandInterface;
use App\Domain\Organization\SigningAuthority\SigningAuthority;
use App\Domain\User\Organization;

final class SaveSigningAuthorityCommand implements CommandInterface
{
    public ?string $name = null;
    public ?string $role = null;
    public ?string $signatoryName = null;

    public function __construct(
        public readonly Organization $organization,
        public readonly ?SigningAuthority $signingAuthority = null,
    ) {
        $this->name = $signingAuthority?->getName();
        $this->role = $signingAuthority?->getRole();
        $this->signatoryName = $signingAuthority?->getSignatoryName();
    }
}
