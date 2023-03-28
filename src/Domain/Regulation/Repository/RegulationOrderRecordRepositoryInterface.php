<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;

interface RegulationOrderRecordRepositoryInterface
{
    public function save(RegulationOrderRecord $regulationOrderRecord): RegulationOrderRecord;

    public function findOneByUuid(string $uuid): RegulationOrderRecord|null;

    public function findRegulationsByOrganization(
        Organization $organization,
        int $maxItemsPerPage,
        int $page,
        bool $permanent,
    ): array;

    public function countRegulationsByOrganization(Organization $organization, bool $permanent): int;

    public function findOneForSummary(string $uuid): array|null;

    public function findRegulationOrdersForDatexFormat(): array;

    public function findOneByOrganizationAndIdentifier(
        Organization $organization,
        string $identifier,
    ): ?RegulationOrderRecord;
}
