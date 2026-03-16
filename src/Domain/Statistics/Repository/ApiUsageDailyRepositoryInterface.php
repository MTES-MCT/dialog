<?php

declare(strict_types=1);

namespace App\Domain\Statistics\Repository;

use App\Domain\Statistics\ApiUsageDaily;

interface ApiUsageDailyRepositoryInterface
{
    public function findOneByDayAndType(\DateTimeInterface $day, string $type): ?ApiUsageDaily;

    /**
     * @return ApiUsageDaily[]
     */
    public function findNotExportedUntil(\DateTimeInterface $until): array;

    public function add(ApiUsageDaily $apiUsageDaily): void;

    public function flush(): void;
}
