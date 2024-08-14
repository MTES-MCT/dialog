<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

use Psr\Log\LoggerInterface;

final class EudonetParisReporterFactory
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function createReporter(): EudonetParisReporter
    {
        return new EudonetParisReporter($this->logger);
    }
}
