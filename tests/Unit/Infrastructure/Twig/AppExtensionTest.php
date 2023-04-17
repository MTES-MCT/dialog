<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Twig;

use App\Infrastructure\Twig\AppExtension;
use App\Tests\TimezoneHelper;
use PHPUnit\Framework\TestCase;

class AppExtensionTest extends TestCase
{
    use TimezoneHelper;

    private AppExtension $extension;

    protected function setUp(): void
    {
        $this->setDefaultTimezone('UTC');
        $this->extension = new AppExtension('Etc/GMT-1'); // Independent of Daylight Saving Time (DST).
    }

    public function testGetFunctions(): void
    {
        $this->assertCount(2, $this->extension->getFunctions());
    }

    public function testFormatDateTimeDateOnly(): void
    {
        $this->assertSame('06/01/2023', $this->extension->formatDateTime(new \DateTimeImmutable('2023-01-06')));
        // Time in $date is ignored
        $this->assertSame('06/01/2023', $this->extension->formatDateTime(new \DateTimeImmutable('2023-01-06T08:30:00')));
    }

    public function testFormatDateTimeWithTime(): void
    {
        $this->assertSame(
            '06/01/2023 à 09h30',
            $this->extension->formatDateTime(new \DateTimeImmutable('2023-01-06 UTC'), new \DateTimeImmutable('08:30:00 UTC')),
        );
        $this->assertSame(
            '07/01/2023 à 11h30',
            $this->extension->formatDateTime(new \DateTimeImmutable('2023-01-06 23:00 UTC'), new \DateTimeImmutable('10:30:00 UTC')),
        );
    }

    private function provideIsFuture(): array
    {
        return [
            // Day after
            [
                'date' => '2023-01-07',
                'time' => null,
                'now' => '2023-01-06 10:30:00',
                'result' => true,
            ],
            [
                'date' => '2023-01-07',
                'time' => '09:00:00',
                'now' => '2023-01-06 10:30:00',
                'result' => true,
            ],
            [
                'date' => '2023-01-07',
                'time' => '00:00:01',
                'now' => '2023-01-06',
                'result' => true,
            ],
            // Day before
            [
                'date' => '2023-01-05',
                'time' => null,
                'now' => '2023-01-06 10:30:00',
                'result' => false,
            ],
            [
                'date' => '2023-01-05',
                'time' => '23:59:59',
                'now' => '2023-01-06',
                'result' => false,
            ],
            // Same day
            [
                'date' => '2023-01-06',
                'time' => '10:29:59',
                'now' => '2023-01-06 10:30:00',
                'result' => false,
            ],
            [
                'date' => '2023-01-06',
                'time' => '10:30:00',
                'now' => '2023-01-06 10:30:00',
                'result' => false,
            ],
            [
                'date' => '2023-01-06',
                'time' => '10:30:01',
                'now' => '2023-01-06 10:30:00',
                'result' => true,
            ],
        ];
    }

    /**
     * @dataProvider provideIsFuture
     */
    public function testIsFuture(string $date, string|null $time, string $now, bool $result): void
    {
        $date = new \DateTimeImmutable($date);
        $time = $time ? new \DateTimeImmutable($time) : null;
        $now = new \DateTimeImmutable($now);
        $this->assertSame($result, $this->extension->isFuture($date, $time, $now));
    }

    public function testIsClientPastDay(): void
    {
        // Jour précédent => true
        // Même jour => false
        // Jour suivant => false
    }
}
