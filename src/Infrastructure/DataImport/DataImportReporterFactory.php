<?php

declare(strict_types=1);

namespace App\Infrastructure\DataImport;

use Psr\Log\LoggerInterface;

final class DataImportReporterFactory
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function createReporter(): DataImportReporter
    {
        return new DataImportReporter($this->logger);
    }
}
