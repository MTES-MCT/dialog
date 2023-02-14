<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\RegulationOrder;
use App\Domain\User\Organization;

interface RegulationOrderRepositoryInterface
{
    public function findOneByUuid(string $uuid): RegulationOrder|null;

    public function findRegulationsByOrganization(
        Organization $organization,
        int $maxItemsPerPage,
        int $page,
        string $status,
    ): array;

    public function countRegulationsByOrganization(Organization $organization, string $status): int;

    public function findOneForSummary(string $uuid): array|null;

    public function findRegulationOrdersForDatexFormat(): array;

    public function save(RegulationOrder $regulationOrder): RegulationOrder;
}
