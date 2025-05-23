<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\DTO\RegulationListFiltersDTO;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;

interface RegulationOrderRecordRepositoryInterface
{
    public function add(RegulationOrderRecord $regulationOrderRecord): RegulationOrderRecord;

    public function findOneByUuid(string $uuid): ?RegulationOrderRecord;

    public function findOneUuidByIdentifierInOrganization(string $identifier, Organization $organization): ?string;

    public function findAllRegulations(
        RegulationListFiltersDTO $dto,
    ): array;

    public function findGeneralInformation(string $uuid): ?array;

    public function findOrganizationUuid(string $uuid): ?string;

    public function findRegulationOrdersForDatexFormat(): array;

    public function getOverallDatesByRegulationUuids(array $uuids): array;

    public function findRegulationOrdersForCifsIncidentFormat(
        array $allowedSources = [],
        array $excludedIdentifiers = [],
        array $allowedLocationIds = [],
        array $excludedOrgUuids = [],
    ): array;

    public function findRegulationOrdersForLitteralisCleanUp(string $organizationId, \DateTimeInterface $laterThan): array;

    public function doesOneExistInOrganizationWithIdentifier(Organization $organization, string $identifier): bool;

    public function findIdentifiersForSource(string $source): array;

    public function countTotalRegulationOrderRecords(): int;

    public function countPublishedRegulationOrderRecords(): int;

    public function countPermanentRegulationOrderRecords(): int;

    public function countTemporaryRegulationOrderRecords(): int;

    public function countRegulationOrderRecordsForOrganizationDuringCurrentMonth(string $uuid): int;
}
