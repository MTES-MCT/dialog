<?php

declare(strict_types=1);

namespace App\Domain\Organization\VisaModel\Repository;

use App\Domain\Organization\VisaModel\VisaModel;

interface VisaModelRepositoryInterface
{
    public function findOneByUuid(string $uuid): ?VisaModel;

    public function findAll(?string $organizationUuid = null): array;

    public function add(VisaModel $visaModel): VisaModel;

    public function remove(VisaModel $visaModel): void;
}
