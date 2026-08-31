<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrdersForCsvExportQuery;
use App\Application\Regulation\View\RegulationCsvRowView;
use App\Infrastructure\Adapter\RegulationExportCsvGenerator;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegulationExportCsvGeneratorTest extends TestCase
{
    private const CSV_PATH = 'csv/regulations.csv';

    private QueryBusInterface&MockObject $queryBus;
    private FilesystemOperator&MockObject $storage;
    private TranslatorInterface&MockObject $translator;
    private RegulationExportCsvGenerator $generator;

    protected function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->storage = $this->createMock(FilesystemOperator::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->method('trans')->willReturnArgument(0);
        $this->generator = new RegulationExportCsvGenerator($this->queryBus, $this->storage, $this->translator);
    }

    private function makeRow(): RegulationCsvRowView
    {
        return new RegulationCsvRowView(
            regulationOrderUuid: 'ro-uuid',
            title: 'Titre',
            category: 'temporaryRegulation',
            status: 'published',
            startDate: new \DateTimeImmutable('2025-01-01'),
            endDate: new \DateTimeImmutable('2025-02-01'),
            organizationName: 'Org',
            organizationSiret: '12345678901234',
            linkPdf: 'https://example.org/doc.pdf',
            measureUuid: 'measure-uuid',
            measureType: 'noEntry',
            locationUuid: 'location-uuid',
            locationType: 'lane',
            locationLabel: 'Rue de la Paix, Paris',
        );
    }

    public function testWriteCsvOutputsBomHeaderAndRows(): void
    {
        $handle = fopen('php://memory', 'r+');
        $this->generator->writeCsv([$this->makeRow()], $handle);

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('arrete_uuid;arrete_titre;arrete_categorie', $content);
        $this->assertStringContainsString(
            'ro-uuid;Titre;regulation.category.temporaryRegulation;regulation.status_badge.published.text;2025-01-01;2025-02-01',
            $content,
        );
        $this->assertStringContainsString('Rue de la Paix, Paris', $content);
    }

    public function testWriteCsvHandlesNullDates(): void
    {
        $row = new RegulationCsvRowView(
            regulationOrderUuid: 'ro-uuid',
            title: 'Titre',
            category: 'temporaryRegulation',
            status: 'published',
            startDate: null,
            endDate: null,
            organizationName: 'Org',
            organizationSiret: '12345678901234',
            linkPdf: '',
            measureUuid: 'measure-uuid',
            measureType: 'noEntry',
            locationUuid: 'location-uuid',
            locationType: 'lane',
            locationLabel: 'Label',
        );

        $handle = fopen('php://memory', 'r+');
        $this->generator->writeCsv([$row], $handle);

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $this->assertStringContainsString(
            'ro-uuid;Titre;regulation.category.temporaryRegulation;regulation.status_badge.published.text;;;Org;12345678901234',
            $content,
        );
    }

    public function testGenerateWritesCsvToStorage(): void
    {
        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(GetRegulationOrdersForCsvExportQuery::class))
            ->willReturn([$this->makeRow()]);

        $this->storage
            ->expects(self::once())
            ->method('writeStream')
            ->with(self::CSV_PATH, self::isType('resource'));

        $this->generator->generate();
    }

    public function testGetCachedCsvReturnsExistingContent(): void
    {
        $this->storage->method('fileExists')->with(self::CSV_PATH)->willReturn(true);
        $this->storage->expects(self::never())->method('writeStream');
        $this->storage->method('read')->with(self::CSV_PATH)->willReturn('cached-content');

        $this->assertSame('cached-content', $this->generator->getCachedCsv());
    }

    public function testGetCachedCsvGeneratesWhenMissing(): void
    {
        $this->queryBus->method('handle')->willReturn([]);
        $this->storage->method('fileExists')->with(self::CSV_PATH)->willReturn(false);
        $this->storage->expects(self::once())->method('writeStream');
        $this->storage->method('read')->with(self::CSV_PATH)->willReturn('generated-content');

        $this->assertSame('generated-content', $this->generator->getCachedCsv());
    }

    public function testGetCachedCsvSizeReturnsExistingSize(): void
    {
        $this->storage->method('fileExists')->with(self::CSV_PATH)->willReturn(true);
        $this->storage->expects(self::never())->method('writeStream');
        $this->storage->method('fileSize')->with(self::CSV_PATH)->willReturn(1234);

        $this->assertSame(1234, $this->generator->getCachedCsvSize());
    }

    public function testGetCachedCsvSizeGeneratesWhenMissing(): void
    {
        $this->queryBus->method('handle')->willReturn([]);
        $this->storage->method('fileExists')->with(self::CSV_PATH)->willReturn(false);
        $this->storage->expects(self::once())->method('writeStream');
        $this->storage->method('fileSize')->with(self::CSV_PATH)->willReturn(42);

        $this->assertSame(42, $this->generator->getCachedCsvSize());
    }
}
