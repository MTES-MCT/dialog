<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;

interface RegulationOrderRecordRepositoryInterface
{
    public function add(RegulationOrderRecord $regulationOrderRecord): RegulationOrderRecord;

    public function findOneByUuid(string $uuid): ?RegulationOrderRecord;

    public function findRegulationsByOrganizations(
        array $organizations,
        int $maxItemsPerPage,
        int $page,
        bool $isPermanent,
    ): array;

    public function findOneForSummary(string $uuid): ?RegulationOrderRecord;

    public function findRegulationOrdersForDatexFormat(): array;

    public function doesOneExistInOrganizationWithIdentifier(Organization $organization, string $identifier): bool;
}
