<?php

declare(strict_types=1);

namespace App\Application\Ign;

final readonly class IgnReportSubmissionResult
{
    public function __construct(
        public string $id,
        public string $status,
    ) {
    }
}
