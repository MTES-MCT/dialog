<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\DataImport;

use App\Infrastructure\DataImport\DataImportReporterFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class DataImportReporterFactoryTest extends TestCase
{
    private $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testCreateReporter(): void
    {
        $factory = new DataImportReporterFactory($this->logger);
        $reporter = $factory->createReporter();

        // Just ensure the logger is used

        $this->logger
            ->expects(self::once())
            ->method('log')
            ->with('error', 'test', ['value' => 1]);

        $reporter->addError('test', ['value' => 1]);
    }
}
