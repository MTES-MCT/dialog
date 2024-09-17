<?php

declare(strict_types=1);

namespace App\Domain\VisaModel\Repository;

use App\Domain\VisaModel\VisaModel;

interface VisaModelRepositoryInterface
{
    public function findOneByUuid(string $uuid): ?VisaModel;

    public function findOrganizationVisaModels(string $organizationUuid): array;

    public function add(VisaModel $visaModel): VisaModel;
}
