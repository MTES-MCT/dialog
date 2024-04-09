<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Twig;

use App\Domain\Regulation\Enum\CritairEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Infrastructure\Adapter\StringUtils;
use App\Infrastructure\FeatureFlagService;
use App\Infrastructure\Twig\AppExtension;
use App\Tests\TimezoneHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints\Expression;
use Symfony\Component\Validator\ConstraintViolation;

class AppExtensionTest extends TestCase
{
    use TimezoneHelper;

    private AppExtension $extension;
    private $featureFlagService;

    protected function setUp(): void
    {
        $this->featureFlagService = $this->createMock(FeatureFlagService::class);
        $this->extension = new AppExtension(
            'Etc/GMT-1', // Independent of Daylight Saving Time (DST).
            new StringUtils(),
            $this->featureFlagService,
        );
    }

    public function testGetFunctions(): void
    {
        $this->assertCount(8, $this->extension->getFunctions());
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
            $this->extension->formatDateTime(new \DateTimeImmutable('2023-01-06'), new \DateTimeImmutable('08:30:00')),
        );
        $this->assertSame(
            '07/01/2023 à 11h30',
            $this->extension->formatDateTime(new \DateTimeImmutable('2023-01-06 23:00'), new \DateTimeImmutable('10:30:00')),
        );
    }

    public function testFormatDateTimeReuseTime(): void
    {
        $this->assertSame(
            '06/01/2023 à 18h00',
            $this->extension->formatDateTime(new \DateTimeImmutable('2023-01-06 17:00'), true),
        );
    }

    public function testFormatTime(): void
    {
        $this->assertSame('18h00', $this->extension->formatTime(new \DateTimeImmutable('2023-01-06 17:00')));
    }

    private function provideFormatNumber(): array
    {
        return [
            [[12], '12'],
            [[12.0], '12'],
            [[12, 0], '12'],
            [[12.0, 0], '12'],
            [[12, 1], '12,0'],
            [[12.0, 1], '12,0'],
            [[12.1, 0], '12'],
            [[12.1, 1], '12,1'],
            [[1000], '1 000'],
            [[1000.45, 3], '1 000,450'],
        ];
    }

    /**
     * @dataProvider provideFormatNumber
     */
    public function testFormatNumber(array $args, $expected): void
    {
        $this->assertSame($expected, $this->extension->formatNumber(...$args));
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

    public function testGetVehicleTypeIconName(): void
    {
        $this->assertSame('emergency-services', $this->extension->getVehicleTypeIconName(VehicleTypeEnum::EMERGENCY_SERVICES->value));
        $this->assertSame('critair', $this->extension->getVehicleTypeIconName(CritairEnum::CRITAIR_4->value));
        $this->assertSame('', $this->extension->getVehicleTypeIconName(VehicleTypeEnum::OTHER->value));
    }

    protected function provideIsFieldsetError(): array
    {
        return [
            [null, false],
            [[], false],
            [['fieldset' => 'other'], false],
            [['fieldset' => 'example'], true],
        ];
    }

    /** @dataProvider provideIsFieldsetError */
    public function testIsFieldsetError(mixed $payload, bool $expected): void
    {
        $error = $this->createMock(FormError::class);
        $cause = $this->createMock(ConstraintViolation::class);
        $constraint = $this->createMock(Expression::class);

        $error->expects(self::once())
            ->method('getCause')
            ->willReturn($cause);

        $cause->expects(self::once())
            ->method('getConstraint')
            ->willReturn($constraint);

        $constraint->payload = $payload;

        $this->assertSame($expected, $this->extension->isFieldsetError($error, 'example'));
    }

    private function provideIsFeatureEnabled(): array
    {
        return [['enabled' => true], ['enabled' => false]];
    }

    /**
     * @dataProvider provideIsFeatureEnabled
     */
    public function testIsFeatureEnabled(bool $enabled): void
    {
        $this->featureFlagService
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('MY_FEATURE')
            ->willReturn($enabled);

        $this->assertSame($enabled, $this->extension->isFeatureEnabled('MY_FEATURE'));
    }
}
