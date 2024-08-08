<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis;

use Psr\Log\LoggerInterface;

final class LitteralisReporterFactory
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function createReporter(): LitteralisReporter
    {
        return new LitteralisReporter($this->logger);
    }
}
