<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Infrastructure\Adapter\DateUtils;
use PHPUnit\Framework\TestCase;

final class DateUtilsTest extends TestCase
{
    public function testTomorrow(): void
    {
        $dateUtils = new DateUtils('Etc/GMT-1');

        $this->assertEquals(new \DateTimeImmutable('tomorrow'), $dateUtils->getTomorrow());
    }

    public function testNow(): void
    {
        $dateUtils = new DateUtils('Etc/GMT-1');

        $this->assertEquals((new \DateTimeImmutable('now'))->format('Y-m-d'), $dateUtils->getNow()->format('Y-m-d'));
    }

    public function testGetMicroTime(): void
    {
        $dateUtils = new DateUtils('Etc/GMT-1');

        $this->assertEqualsWithDelta(microtime(true), $dateUtils->getMicroTime(), 0.1);
    }

    public function testMergeDateAndTime(): void
    {
        $dateUtils = new DateUtils('Etc/GMT-1');
        $date1 = new \DateTime('2023-10-09 23:00:00 UTC');
        $date2 = new \DateTime('08:00:00');

        $this->assertEquals(new \DateTime('2023-10-10 08:00:00'), $dateUtils->mergeDateAndTime($date1, $date2));
    }

    private function provideAddDays(): array
    {
        return [
            [
                'date' => new \DateTime('2023-10-09 23:00:00 UTC'),
                'days' => 0,
                'result' => new \DateTime('2023-10-09 23:00:00 UTC'),
            ],
            [
                'date' => new \DateTime('2023-10-09 23:00:00 UTC'),
                'days' => 1,
                'result' => new \DateTime('2023-10-10 23:00:00 UTC'),
            ],
            [
                'date' => new \DateTime('2023-10-01 23:00:00 UTC'),
                'days' => -1,
                'result' => new \DateTime('2023-09-30 23:00:00 UTC'),
            ],
            [
                'date' => new \DateTime('2024-02-29 23:00:00 UTC'),
                'days' => 3,
                'result' => new \DateTime('2024-03-03 23:00:00 UTC'),
            ],
        ];
    }

    /**
     * @dataProvider provideAddDays
     */
    public function testAddDays(\DateTimeInterface $date, int $days, \DateTimeInterface $result): void
    {
        $dateUtils = new DateUtils('Etc/GMT-1');

        $this->assertEquals($result, $dateUtils->addDays($date, $days));
    }
}
