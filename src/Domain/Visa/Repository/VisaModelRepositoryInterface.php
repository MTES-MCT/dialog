<?php

declare(strict_types=1);

namespace App\Domain\Visa\Repository;

interface VisaModelRepositoryInterface
{
    public function findOrganizationVisaModels(string $organizationUuid): array;
}
