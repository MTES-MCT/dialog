<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\User\OrganizationRegulationAccessInterface;

class RegulationForLocationView implements OrganizationRegulationAccessInterface
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $organizationUuid,
        public readonly string $regulationOrderUuid,
        public readonly string $status,
        ) {
    }

    public function getOrganizationUuid(): ?string
    {
        return $this->organizationUuid;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
