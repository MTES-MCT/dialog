<?php

declare(strict_types=1);

namespace App\Application;

interface CsvExporterInterface
{
    /**
     * @return string Contenu CSV
     */
    public function export(array $data): string;
}
