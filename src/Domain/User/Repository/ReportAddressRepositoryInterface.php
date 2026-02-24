<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\ReportAddress;

interface ReportAddressRepositoryInterface
{
    public function add(ReportAddress $reportAddress): ReportAddress;

    public function findOneByIgnReportId(string $ignReportId): ?ReportAddress;

    /** @return ReportAddress[] */
    public function findAllPendingIgnReports(): array;
}
