<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Mapper\Transformers;

use App\Infrastructure\Mapper\Transformers\DateTimeTransformers;
use PHPUnit\Framework\TestCase;

final class DateTimeTransformersTest extends TestCase
{
    public function testFromIsoReturnsNullWhenValueIsNull(): void
    {
        self::assertNull(DateTimeTransformers::fromIso(null));
    }

    public function testFromIsoReturnsNullWhenValueIsEmpty(): void
    {
        self::assertNull(DateTimeTransformers::fromIso(''));
    }

    public function testFromIsoReturnsDateTimeImmutableForValidIsoDate(): void
    {
        $result = DateTimeTransformers::fromIso('2025-01-15');

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('2025-01-15', $result->format('Y-m-d'));
    }

    public function testFromIsoReturnsDateTimeImmutableForValidIsoDateTime(): void
    {
        $result = DateTimeTransformers::fromIso('2025-01-15T14:30:00Z');

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('2025-01-15T14:30:00+00:00', $result->format('c'));
    }

    public function testFromIsoReturnsDateTimeImmutableForValidIsoDateTimeWithTimezone(): void
    {
        $result = DateTimeTransformers::fromIso('2025-01-15T14:30:00+02:00');

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('2025-01-15T14:30:00+02:00', $result->format('c'));
    }

    public function testFromIsoReturnsNullForInvalidFormat(): void
    {
        self::assertNull(DateTimeTransformers::fromIso('invalid-date'));
    }

    public function testFromIsoReturnsNullForInvalidDate(): void
    {
        self::assertNull(DateTimeTransformers::fromIso('2025-13-45'));
    }
}
