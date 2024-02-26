<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\BacIdf;

use App\Infrastructure\BacIdf\BacIdfExtractor;
use PHPUnit\Framework\TestCase;

final class BacIdfExtractorTest extends TestCase
{
    private $decreesFile;
    private $records;

    protected function setUp(): void
    {
        $this->decreesFile = 'tests/tmp/decrees.json';

        register_shutdown_function(function () {
            if (file_exists($this->decreesFile)) {
                unlink($this->decreesFile);
            }
        });

        $this->records = [
            ['ARR_REF' => '065c24d6-f230-754f-8000-0146e7a9526d'],
            ['ARR_REF' => '065c24d8-1c13-7e6a-8000-23208c4f7396'],
            ['ARR_REF' => '065c24d8-6e0e-7714-8000-2201d1093adf'],
        ];

        file_put_contents($this->decreesFile, json_encode($this->records));
    }

    public function testExtract(): void
    {
        $extractor = new BacIdfExtractor($this->decreesFile);

        $this->assertEquals(
            $this->records,
            iterator_to_array($extractor->iterExtract([])),
        );
    }

    public function testExtractWithIgnoreIDs(): void
    {
        $extractor = new BacIdfExtractor($this->decreesFile);

        $this->assertEquals(
            [$this->records[0]],
            iterator_to_array($extractor->iterExtract(['065c24d8-1c13-7e6a-8000-23208c4f7396', '065c24d8-6e0e-7714-8000-2201d1093adf'])),
        );
    }
}
