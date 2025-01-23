<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Infrastructure\Adapter\CsvParser;
use PHPUnit\Framework\TestCase;

final class CsvParserTest extends TestCase
{
    private $csvParser;

    protected function setUp(): void
    {
        $this->csvParser = new CsvParser();
    }

    public function testParseEmpty(): void
    {
        $this->assertEquals([], $this->csvParser->parseAssociative(''));
    }

    public function testParseCsv(): void
    {
        $csv = '
            id,pr,abs
            id1,12,40
            id2,3;4,53
            id3,,
        ';

        $this->assertEquals(
            [
                ['id' => 'id1', 'pr' => '12', 'abs' => '40'],
                ['id' => 'id2', 'pr' => '3;4', 'abs' => '53'],
                ['id' => 'id3', 'pr' => '', 'abs' => ''],
            ],
            $this->csvParser->parseAssociative($csv),
        );
    }
}
