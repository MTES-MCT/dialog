<?php

declare(strict_types=1);

namespace App\Domain\User;

interface OrganizationRegulationAccessInterface
{
    public function getOrganizationUuid(): ?string;
}
