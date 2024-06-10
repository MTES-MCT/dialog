<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\RegulationOrderIssue;

interface RegulationOrderIssueRepositoryInterface
{
    public function add(RegulationOrderIssue $regulationOrderIssue): RegulationOrderIssue;
}
