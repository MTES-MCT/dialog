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

    public function testFormatDateTimeDateOnly(): void
    {
        $dateUtils = new DateUtils('Etc/GMT-1');

        $this->assertSame('06/01/2023', $dateUtils->formatDateTime(new \DateTimeImmutable('2023-01-06')));
        // Time in $date is ignored
        $this->assertSame('06/01/2023', $dateUtils->formatDateTime(new \DateTimeImmutable('2023-01-06T08:30:00')));
    }

    public function testFormatDateTimeWithTime(): void
    {
        $dateUtils = new DateUtils('Etc/GMT-1');

        $this->assertSame(
            '06/01/2023 à 09h30',
            $dateUtils->formatDateTime(new \DateTimeImmutable('2023-01-06'), new \DateTimeImmutable('08:30:00')),
        );
        $this->assertSame(
            '07/01/2023 à 11h30',
            $dateUtils->formatDateTime(new \DateTimeImmutable('2023-01-06 23:00'), new \DateTimeImmutable('10:30:00')),
        );
    }

    public function testFormatDateTimeReuseTime(): void
    {
        $dateUtils = new DateUtils('Etc/GMT-1');

        $this->assertSame(
            '06/01/2023 à 18h00',
            $dateUtils->formatDateTime(new \DateTimeImmutable('2023-01-06 17:00'), true),
        );
    }

    private function provideIsPast(): array
    {
        return [
            // Day after
            [
                'date' => '2023-01-07',
                'now' => '2023-01-06',
                'result' => false,
            ],
            // Day before
            [
                'date' => '2023-01-05',
                'now' => '2023-01-06',
                'result' => true,
            ],
            // Same day
            [
                'date' => '2023-01-06',
                'now' => '2023-01-06',
                'result' => false,
            ],
            // Same day  (time does not matter)
            [
                'date' => '2023-01-06T11:30:00',
                'now' => '2023-01-06T08:00:00',
                'result' => false,
            ],
        ];
    }

    /**
     * @dataProvider provideIsPast
     */
    public function testIsClientPastDay(string $date, string $now, bool $result): void
    {
        $dateUtils = new DateUtils('Etc/GMT-1');

        $date = new \DateTimeImmutable($date);
        $now = new \DateTimeImmutable($now);
        $this->assertSame($result, $dateUtils->isClientPastDay($date, $now));
    }

    private function provideIsFuture(): array
    {
        return [
            // Day after
            [
                'date' => '2023-01-07',
                'now' => '2023-01-06',
                'result' => true,
            ],
            // Day before
            [
                'date' => '2023-01-05',
                'now' => '2023-01-06',
                'result' => false,
            ],
            // Same day
            [
                'date' => '2023-01-06',
                'now' => '2023-01-06',
                'result' => false,
            ],
            // same day (time does not matter)
            [
                'date' => '2023-01-06T08:00:00',
                'now' => '2023-01-06T11:00:00',
                'result' => false,
            ],
        ];
    }

    /**
     * @dataProvider provideIsFuture
     */
    public function testIsClientFutureDay(string $date, string $now, bool $result): void
    {
        $dateUtils = new DateUtils('Etc/GMT-1');

        $date = new \DateTimeImmutable($date);
        $now = new \DateTimeImmutable($now);
        $this->assertSame($result, $dateUtils->isClientFutureDay($date, $now));
    }
}
