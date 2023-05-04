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
        $date = new \DateTimeImmutable($date);
        $now = new \DateTimeImmutable($now);
        $this->assertSame($result, $this->extension->isClientPastDay($date, $now));
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
        $date = new \DateTimeImmutable($date);
        $now = new \DateTimeImmutable($now);
        $this->assertSame($result, $this->extension->isClientFutureDay($date, $now));
    }

    private function provideRegulationGeneralInfoTitle(): array
    {
        return [
            [
                'description' => 'Description 1',
                'expectedTitle' => 'Description 1',
            ],
            [
                'description' => 'Just belowwwwww the truncature limit',
                'expectedTitle' => 'Just belowwwwww the truncature limit',
            ],
            [
                'description' => 'Just aboveeeeeee the truncature limit',
                'expectedTitle' => 'Just aboveeeeeee the truncature...',
            ],
            [
                'description' => 'Small description. More text',
                'expectedTitle' => 'Small description',
            ],
            [
                'description' => 'Very long description above the limit. More text',
                'expectedTitle' => 'Very long description above the...',
            ],
            [
                'description' => 'Travaux sur la N.02 à Tours. More text',
                'expectedTitle' => 'Travaux sur la N.02 à Tours',
            ],
        ];
    }

    /**
     * @dataProvider provideRegulationGeneralInfoTitle
     */
    public function testRegulationGeneralInfoTitle(string $description, string $expectedTitle): void
    {
        $this->assertSame($expectedTitle, $this->extension->getRegulationGeneralInfoTitle($description));
    }
}
