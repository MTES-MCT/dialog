<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\BacIdf;

final class BacIdfCityProcessor implements BacIdfCityProcessorInterface
{
    private array $inseeCodeToSiret;

    public function __construct(
        string $bacIdfCitiesFile,
    ) {
        $rows = self::readCsvWithHeader($bacIdfCitiesFile);

        $inseeCodeToSiret = [];

        foreach ($rows as $row) {
            $inseeCodeToSiret[$row['city_code']] = $row['siret'];
        }

        $this->inseeCodeToSiret = $inseeCodeToSiret;
    }

    private static function readCsvWithHeader(string $filename): array
    {
        // Credit: Inspired by: https://www.php.net/manual/fr/function.str-getcsv.php#117692
        $rows = [];
        $header = [];

        foreach (file($filename) as $index => $line) {
            $row = str_getcsv($line, separator: ';');

            if ($index === 0) {
                $header = $row;
            } else {
                $rows[] = array_combine($header, $row);
            }
        }

        return $rows;
    }

    public function getSiretFromInseeCode(string $inseeCode): ?string
    {
        if (!\array_key_exists($inseeCode, $this->inseeCodeToSiret)) {
            return null;
        }

        return $this->inseeCodeToSiret[$inseeCode];
    }
}
