<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

class CsvParser
{
    public function parseAssociative(string $csv): array
    {
        $csv = trim($csv);

        if (!$csv) {
            return [];
        }

        $rows = [];

        $lines = array_map(fn ($t) => trim($t), explode(PHP_EOL, $csv));

        $fieldNames = str_getcsv($lines[0], ',');

        foreach (\array_slice($lines, 1) as $line) {
            $values = str_getcsv($line, ',');
            $row = [];

            foreach ($fieldNames as $index => $name) {
                $row[$name] = $values[$index];
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
