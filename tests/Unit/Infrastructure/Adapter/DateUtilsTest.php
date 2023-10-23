<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Infrastructure\Adapter\DateUtils;
use PHPUnit\Framework\TestCase;

final class DateUtilsTest extends TestCase
{
    public function testTomorrow(): void
    {
        $dateUtils = new DateUtils('Europe/Paris');

        $this->assertEquals(new \DateTimeImmutable('tomorrow'), $dateUtils->getTomorrow());
    }

    public function testNow(): void
    {
        $dateUtils = new DateUtils('Europe/Paris');

        $this->assertEquals((new \DateTimeImmutable('now'))->format('Y-m-d'), $dateUtils->getNow()->format('Y-m-d'));
    }

    public function testGetMicroTime(): void
    {
        $dateUtils = new DateUtils('Europe/Paris');

        $this->assertEqualsWithDelta(microtime(true), $dateUtils->getMicroTime(), 0.1);
    }
}
