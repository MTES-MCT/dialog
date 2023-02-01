<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\RegulationOrderRecord;

interface RegulationOrderRecordRepositoryInterface
{
    public function save(RegulationOrderRecord $regulationOrderRecord): RegulationOrderRecord;

    public function findOneByUuid(string $uuid): RegulationOrderRecord|null;

    public function findRegulations(int $maxItemsPerPage, int $page, string $status): array;

    public function countRegulations(string $status): int;

    public function findOneForSummary(string $uuid): array|null;

    public function findRegulationOrdersForDatexFormat(): array;
}
