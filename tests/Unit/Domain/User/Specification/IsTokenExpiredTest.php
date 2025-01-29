<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Specification;

use App\Application\DateUtilsInterface;
use App\Domain\User\Specification\IsTokenExpired;
use App\Domain\User\Token;
use PHPUnit\Framework\TestCase;

final class IsTokenExpiredTest extends TestCase
{
    public function testTokenExpired(): void
    {
        $token = $this->createMock(Token::class);
        $token
            ->expects(self::once())
            ->method('getExpirationDate')
            ->willReturn(new \DateTime('2023-08-30 19:00:00'));

        $dateUtils = $this->createMock(DateUtilsInterface::class);
        $dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn(new \DateTimeImmutable('2023-08-31 09:00:00'));

        $pattern = new IsTokenExpired($dateUtils);
        $this->assertTrue($pattern->isSatisfiedBy($token));
    }

    public function testTokenNotExpired(): void
    {
        $token = $this->createMock(Token::class);
        $token
            ->expects(self::once())
            ->method('getExpirationDate')
            ->willReturn(new \DateTime('2023-08-30 19:30:00'));

        $dateUtils = $this->createMock(DateUtilsInterface::class);
        $dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn(new \DateTimeImmutable('2023-08-30 19:00:00'));

        $pattern = new IsTokenExpired($dateUtils);
        $this->assertFalse($pattern->isSatisfiedBy($token));
    }
}
