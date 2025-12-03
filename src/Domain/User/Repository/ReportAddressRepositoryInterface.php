<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\ReportAddress;

interface ReportAddressRepositoryInterface
{
    public function add(ReportAddress $reportAddress): ReportAddress;
}
