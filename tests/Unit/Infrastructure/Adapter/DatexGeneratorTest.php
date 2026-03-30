<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use App\Infrastructure\Adapter\DatexGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DatexGeneratorTest extends TestCase
{
    private \Twig\Environment&MockObject $twig;
    private DateUtilsInterface&MockObject $dateUtils;
    private QueryBusInterface&MockObject $queryBus;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(\Twig\Environment::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);

        $this->tmpDir = sys_get_temp_dir() . '/datex_generator_test_' . uniqid();
    }

    protected function tearDown(): void
    {
        $filePath = $this->tmpDir . '/var/datex/regulations.xml';
        $tmpFile = $filePath . '.tmp';

        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Remove directories created during tests
        @rmdir($this->tmpDir . '/var/datex');
        @rmdir($this->tmpDir . '/var');
        @rmdir($this->tmpDir);
    }

    public function testGetDatexFilePath(): void
    {
        $generator = new DatexGenerator(
            $this->twig,
            $this->dateUtils,
            $this->queryBus,
            $this->tmpDir,
        );

        $this->assertSame($this->tmpDir . '/var/datex/regulations.xml', $generator->getDatexFilePath());
    }

    public function testGenerateCreatesDirectoryAndFile(): void
    {
        $now = new \DateTimeImmutable('2025-01-01');
        $regulationOrders = ['order1', 'order2'];

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with($this->equalTo(new GetRegulationOrdersToDatexFormatQuery()))
            ->willReturn($regulationOrders);

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with('api/regulations.xml.twig', [
                'publicationTime' => $now,
                'regulationOrders' => $regulationOrders,
            ])
            ->willReturn('<xml>generated content</xml>');

        $generator = new DatexGenerator(
            $this->twig,
            $this->dateUtils,
            $this->queryBus,
            $this->tmpDir,
        );

        $filePath = $generator->getDatexFilePath();

        $this->assertDirectoryDoesNotExist(\dirname($filePath));

        $generator->generate();

        $this->assertDirectoryExists(\dirname($filePath));
        $this->assertFileExists($filePath);
        $this->assertSame('<xml>generated content</xml>', file_get_contents($filePath));
    }

    public function testGenerateOverwritesExistingFile(): void
    {
        $dir = $this->tmpDir . '/var/datex';
        mkdir($dir, 0o755, true);
        file_put_contents($dir . '/regulations.xml', '<xml>old content</xml>');

        $now = new \DateTimeImmutable('2025-06-15');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->willReturn([]);

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn($now);

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->willReturn('<xml>new content</xml>');

        $generator = new DatexGenerator(
            $this->twig,
            $this->dateUtils,
            $this->queryBus,
            $this->tmpDir,
        );

        $generator->generate();

        $this->assertSame('<xml>new content</xml>', file_get_contents($generator->getDatexFilePath()));
        // Ensure tmp file is cleaned up (renamed, not left behind)
        $this->assertFileDoesNotExist($generator->getDatexFilePath() . '.tmp');
    }
}
