<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\EudonetParis;

use App\Infrastructure\EudonetParis\EudonetParisLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

final class EudonetParisLoggerTest extends TestCase
{
    private $kernel;
    private $testLogDir;
    private $fs;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->testLogDir = '/tmp/eudonet_paris' . uniqid((string) mt_rand(), true);
        $this->fs = new Filesystem();
        $this->fs->mkdir($this->testLogDir);
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->testLogDir);
    }

    public function testLog(): void
    {
        $this->kernel
            ->expects(self::once())
            ->method('getEnvironment')
            ->willReturn('testing');

        $dateUTC = new \DateTimeImmutable('2023-08-31 08:30:00');

        $expectedFilePath = "$this->testLogDir/Import20230831083000.testing.log";
        $this->assertFalse($this->fs->exists($expectedFilePath));

        $logger = new EudonetParisLogger($this->kernel, $this->fs, $this->testLogDir);
        $logger->log('Hello, world!', $dateUTC);

        $this->assertTrue($this->fs->exists($expectedFilePath));
        $this->assertSame('Hello, world!', file_get_contents($expectedFilePath));
    }
}
