<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Query\GetRegulationOrdersForCsvExportQuery;
use App\Application\Regulation\RegulationExportCsvGeneratorInterface;
use App\Application\Regulation\View\RegulationCsvRowView;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegulationExportCsvGenerator implements RegulationExportCsvGeneratorInterface
{
    private const CSV_PATH = 'csv/regulations.csv';
    private const DELIMITER = ';';

    // BOM UTF-8 : garantit l'affichage correct des caractères accentués dans Excel.
    private const BOM = "\xEF\xBB\xBF";

    private const HEADER = [
        'arrete_uuid',
        'arrete_titre',
        'arrete_categorie',
        'arrete_statut',
        'date_debut',
        'date_fin',
        'organisation',
        'lien_pdf',
        'mesure_uuid',
        'type_restriction',
        'emprise_uuid',
        'emprise_type',
        'emprise_libelle',
    ];

    public function __construct(
        private QueryBusInterface $queryBus,
        private FilesystemOperator $storage,
        private TranslatorInterface $translator,
    ) {
    }

    public function generate(): void
    {
        /** @var RegulationCsvRowView[] $rows */
        $rows = $this->queryBus->handle(new GetRegulationOrdersForCsvExportQuery());

        $tmpFile = tempnam(sys_get_temp_dir(), 'regulations_csv');
        $handle = fopen($tmpFile, 'w');

        $this->writeCsv($rows, $handle);

        fclose($handle);

        $readStream = fopen($tmpFile, 'r');
        $this->storage->writeStream(self::CSV_PATH, $readStream);

        if (\is_resource($readStream)) {
            fclose($readStream);
        }

        unlink($tmpFile);
    }

    public function getCachedCsv(): string
    {
        if (!$this->storage->fileExists(self::CSV_PATH)) {
            $this->generate();
        }

        return $this->storage->read(self::CSV_PATH);
    }

    public function getCachedCsvSize(): int
    {
        if (!$this->storage->fileExists(self::CSV_PATH)) {
            $this->generate();
        }

        return $this->storage->fileSize(self::CSV_PATH);
    }

    public function writeCsv(iterable $rows, $handle): void
    {
        fwrite($handle, self::BOM);
        fputcsv($handle, self::HEADER, self::DELIMITER, '"', '');

        foreach ($rows as $row) {
            fputcsv($handle, $this->toColumns($row), self::DELIMITER, '"', '');
        }
    }

    /**
     * @return string[]
     */
    private function toColumns(RegulationCsvRowView $row): array
    {
        return [
            $row->regulationOrderUuid,
            $row->title,
            $this->translator->trans('regulation.category.' . $row->category),
            $this->translator->trans('regulation.status_badge.' . $row->status . '.text'),
            $row->startDate?->format('Y-m-d') ?? '',
            $row->endDate?->format('Y-m-d') ?? '',
            $row->organizationName,
            $row->linkPdf,
            $row->measureUuid,
            $this->translator->trans('regulation.measure.type.' . $row->measureType),
            $row->locationUuid,
            $this->translator->trans('regulation.location.road.type.' . $row->locationType),
            $row->locationLabel,
        ];
    }
}
