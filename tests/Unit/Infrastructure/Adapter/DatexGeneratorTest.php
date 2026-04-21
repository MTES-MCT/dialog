<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\DateUtilsInterface;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrdersToDatexFormatQuery;
use App\Infrastructure\Adapter\DatexGenerator;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DatexGeneratorTest extends TestCase
{
    private \Twig\Environment&MockObject $twig;
    private DateUtilsInterface&MockObject $dateUtils;
    private QueryBusInterface&MockObject $queryBus;
    private Filesystem $storage;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(\Twig\Environment::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);

        $this->storage = new Filesystem(new InMemoryFilesystemAdapter());
    }

    public function testGenerateCreatesFile(): void
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
            ->method('display')
            ->with('api/regulations.xml.twig', [
                'publicationTime' => $now,
                'regulationOrders' => $regulationOrders,
            ])
            ->willReturnCallback(function () {
                echo '<xml>generated content</xml>';
            });

        $generator = new DatexGenerator(
            $this->twig,
            $this->dateUtils,
            $this->queryBus,
            $this->storage,
        );

        $this->assertFalse($this->storage->fileExists('datex/regulations.xml'));

        $generator->generate();

        $this->assertTrue($this->storage->fileExists('datex/regulations.xml'));
        $this->assertSame('<xml>generated content</xml>', $this->storage->read('datex/regulations.xml'));
    }

    public function testGenerateOverwritesExistingFile(): void
    {
        $this->storage->write('datex/regulations.xml', '<xml>old content</xml>');

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
            ->method('display')
            ->willReturnCallback(function () {
                echo '<xml>new content</xml>';
            });

        $generator = new DatexGenerator(
            $this->twig,
            $this->dateUtils,
            $this->queryBus,
            $this->storage,
        );

        $generator->generate();

        $this->assertSame('<xml>new content</xml>', $this->storage->read('datex/regulations.xml'));
    }
}
