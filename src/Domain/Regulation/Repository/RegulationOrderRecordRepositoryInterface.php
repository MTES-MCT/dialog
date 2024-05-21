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

    public function findGeneralInformation(string $uuid): ?array;

    public function findRegulationOrdersForDatexFormat(): array;

    public function findRegulationOrdersForCifsIncidentFormat(array $allowedLocationIds = []): array;

    public function doesOneExistInOrganizationWithIdentifier(Organization $organization, string $identifier): bool;

    public function findIdentifiersForSource(string $source): array;

    public function countTotalRegulationOrderRecords(): int;

    public function countPublishedRegulationOrderRecords(): int;

    public function countPermanentRegulationOrderRecords(): int;

    public function countTemporaryRegulationOrderRecords(): int;
}
