<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\RegulationOrderRecord;

interface RegulationOrderRecordRepositoryInterface
{
    public function save(RegulationOrderRecord $regulationOrderRecord): RegulationOrderRecord;

    public function findOneByUuid(string $uuid): RegulationOrderRecord|null;

    public function findAll(): array;
}
