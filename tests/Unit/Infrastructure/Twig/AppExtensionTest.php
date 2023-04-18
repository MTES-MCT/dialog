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
        $this->assertCount(3, $this->extension->getFunctions());
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
<<<<<<< HEAD
            // Same day  (time does not matter)
            [
                'date' => '2023-01-06T11:30:00',
                'now' => '2023-01-06T08:00:00',
                'result' => false,
            ],
=======
>>>>>>> 3bd8435 (rename functions and tests)
        ];
    }

    /**
     * @dataProvider provideIsPast
     */
<<<<<<< HEAD
    public function testIsClientPastDay(string $date, string $now, bool $result): void
=======
    
    public function testIsClientPastDay(string $date,string $now, bool $result): void
>>>>>>> 3bd8435 (rename functions and tests)
    {
        $date = new \DateTimeImmutable($date);
        $now = new \DateTimeImmutable($now);
        $this->assertSame($result, $this->extension->isClientPastDay($date, $now));
    }
<<<<<<< HEAD

=======
    
>>>>>>> 3bd8435 (rename functions and tests)
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
<<<<<<< HEAD
            // same day (time does not matter)
            [
                'date' => '2023-01-06T08:00:00',
                'now' => '2023-01-06T11:00:00',
                'result' => false,
            ],
=======
>>>>>>> 3bd8435 (rename functions and tests)
        ];
    }
    /**
     * @dataProvider provideIsFuture
     */
<<<<<<< HEAD
    public function testIsClientFutureDay(string $date, string $now, bool $result): void
=======

    public function testIsClientFutureDay(string $date,string $now, bool $result):void
>>>>>>> 3bd8435 (rename functions and tests)
    {
        $date = new \DateTimeImmutable($date);
        $now = new \DateTimeImmutable($now);
        $this->assertSame($result, $this->extension->isClientFutureDay($date, $now));
<<<<<<< HEAD
    }

    public function testIsClientPastDay(): void
    {
        // Jour précédent => true
        // Même jour => false
        // Jour suivant => false
=======
>>>>>>> 3bd8435 (rename functions and tests)
    }
}
