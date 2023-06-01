<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;

interface RegulationOrderRecordRepositoryInterface
{
    public function add(RegulationOrderRecord $regulationOrderRecord): RegulationOrderRecord;

    public function findOneByUuid(string $uuid): ?RegulationOrderRecord;

    public function findRegulationsByOrganization(
        Organization $organization,
        int $maxItemsPerPage,
        int $page,
        bool $isPermanent,
    ): array;

    public function findOneForSummary(string $uuid): ?RegulationOrderRecord;

    public function findRegulationOrdersForDatexFormat(): array;

    public function findOneByOrganizationAndIdentifier(
        Organization $organization,
        string $identifier,
    ): ?RegulationOrderRecord;
}
