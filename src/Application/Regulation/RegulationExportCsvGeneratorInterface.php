<?php

declare(strict_types=1);

namespace App\Application\Regulation;

use App\Application\Regulation\View\RegulationCsvRowView;

interface RegulationExportCsvGeneratorInterface
{
    public function generate(): void;

    public function getCachedCsv(): string;

    public function getCachedCsvSize(): int;

    /**
     * @param iterable<RegulationCsvRowView> $rows
     * @param resource                       $handle
     */
    public function writeCsv(iterable $rows, $handle): void;
}
