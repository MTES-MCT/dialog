<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Symfony\Command;

use App\Infrastructure\Twig\AppExtension;
use PHPUnit\Framework\TestCase;

class AppExtensionTest extends TestCase
{
    private AppExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new AppExtension();
    }

    public function testGetFunctions(): void
    {
        $this->assertCount(2, $this->extension->getFunctions());
    }

    public function testFormatDateTimeDateOnly(): void
    {
        $this->assertSame('06/01/2023', $this->extension->formatDateTime(new \DateTimeImmutable('2023-01-06')));
        // Time in $date is ignored
        $this->assertSame('06/01/2023', $this->extension->formatDateTime(new \DateTimeImmutable('2023-01-06T08:30:00+00:00')));
    }

    public function testFormatDateTimeWithTime(): void
    {
        $this->assertSame(
            '06/01/2023 à 09h30',
            $this->extension->formatDateTime(new \DateTimeImmutable('2023-01-06'), new \DateTimeImmutable('08:30:00'))
        );
        // Time in $date is ignored
        $this->assertSame(
            '06/01/2023 à 11h30',
            $this->extension->formatDateTime(new \DateTimeImmutable('2023-01-06T08:30:00+00:00'), new \DateTimeImmutable('10:30:00'))
        );
    }

    private function provideIsFuture(): array
    {
        return [
            // Day after
            [
                'date' => new \DateTimeImmutable('2023-01-07'),
                'time' => null,
                'now' => new \DateTimeImmutable('2023-01-06 10:30:00'),
                'result' => true,
            ],
            [
                'date' => new \DateTimeImmutable('2023-01-07'),
                'time' => new \DateTimeImmutable('09:00:00'),
                'now' => new \DateTimeImmutable('2023-01-06 10:30:00 '),
                'result' => true,
            ],
            [
                'date' => new \DateTimeImmutable('2023-01-07'),
                'time' => new \DateTimeImmutable('00:00:01'),
                'now' => new \DateTimeImmutable('2023-01-06'),
                'result' => true,
            ],
            // Day before
            [
                'date' => new \DateTimeImmutable('2023-01-05'),
                'time' => null,
                'now' => new \DateTimeImmutable('2023-01-06 10:30:00'),
                'result' => false,
            ],
            [
                'date' => new \DateTimeImmutable('2023-01-05'),
                'time' => new \DateTimeImmutable('23:59:59'),
                'now' => new \DateTimeImmutable('2023-01-06'),
                'result' => false,
            ],
            // Same day
            [
                'date' => new \DateTimeImmutable('2023-01-06'),
                'time' => new \DateTimeImmutable('10:29:59'),
                'now' => new \DateTimeImmutable('2023-01-06 10:30:00'),
                'result' => false,
            ],
            [
                'date' => new \DateTimeImmutable('2023-01-06'),
                'time' => new \DateTimeImmutable('10:30:00'),
                'now' => new \DateTimeImmutable('2023-01-06 10:30:00'),
                'result' => false,
            ],
            [
                'date' => new \DateTimeImmutable('2023-01-06'),
                'time' => new \DateTimeImmutable('10:30:01'),
                'now' => new \DateTimeImmutable('2023-01-06 10:30:00'),
                'result' => true,
            ],
        ];
    }

    /**
     * @dataProvider provideIsFuture
     */
    public function testIsFuture($date, $time, $now, $result): void
    {
        $this->assertSame($result, $this->extension->isFuture($date, $time, $now));
    }
}
