<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;

interface RegulationOrderRecordRepositoryInterface
{
    public function add(RegulationOrderRecord $regulationOrderRecord): RegulationOrderRecord;

    public function findOneByUuid(string $uuid): RegulationOrderRecord|null;

    public function findRegulationsByOrganization(
        Organization $organization,
        int $maxItemsPerPage,
        int $page,
        bool $isPermanent,
    ): array;

    public function countRegulationsByOrganization(Organization $organization, bool $isPermanent): int;

    public function findOneForSummary(string $uuid): array|null;

    public function findRegulationOrdersForDatexFormat(): array;

    public function findOneGeneralInfoByUuid(string $uuid): array|null;

    public function findOneForLocationByUuid(string $uuid): array|null;

    public function findOneByOrganizationAndIdentifier(
        Organization $organization,
        string $identifier,
    ): ?RegulationOrderRecord;
}
