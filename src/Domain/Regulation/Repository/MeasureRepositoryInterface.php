<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\Measure;

interface MeasureRepositoryInterface
{
    public function findOneByUuid(string $uuid): ?Measure;

    public function add(Measure $measure): Measure;

    public function delete(Measure $measure): void;

    public function findByRegulationOrderRecordUuid(string $uuid);
}
