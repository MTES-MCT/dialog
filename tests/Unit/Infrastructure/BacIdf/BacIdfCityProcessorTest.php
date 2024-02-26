<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\BacIdf;

use App\Infrastructure\BacIdf\BacIdfCityProcessor;
use PHPUnit\Framework\TestCase;

final class BacIdfCityProcessorTest extends TestCase
{
    private $citiesFile;

    protected function setUp(): void
    {
        $this->citiesFile = 'tests/tmp/cities.csv';

        register_shutdown_function(function () {
            if (file_exists($this->citiesFile)) {
                unlink($this->citiesFile);
            }
        });

        $rows = [
            ['city_code', 'siret'],
            ['93027', '21930027400012'],
        ];

        $fp = fopen($this->citiesFile, 'w');

        foreach ($rows as $row) {
            fputcsv($fp, $row, separator: ';');
        }

        fclose($fp);
    }

    public function testGetSiretFromInseeCode(): void
    {
        $cityProcessor = new BacIdfCityProcessor($this->citiesFile);

        $this->assertSame('21930027400012', $cityProcessor->getSiretFromInseeCode('93027'));
        $this->assertNull($cityProcessor->getSiretFromInseeCode('doesnotexist'));
    }
}
