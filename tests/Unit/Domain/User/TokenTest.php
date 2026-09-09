<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\Enum\TokenTypeEnum;
use App\Domain\User\Token;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class TokenTest extends TestCase
{
    public function testGetters(): void
    {
        $user = $this->createMock(User::class);
        $createdAt = new \DateTime('1989-08-17');
        $expirationDate = new \DateTime('1989-09-17');
        $token = new Token(
            '9cebe00d-04d8-48da-89b1-059f6b7bfe44',
            'token',
            TokenTypeEnum::FORGOT_PASSWORD->value,
            $user,
            $createdAt,
            $expirationDate,
        );

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $token->getUuid());
        $this->assertSame('token', $token->getToken());
        $this->assertSame(TokenTypeEnum::FORGOT_PASSWORD->value, $token->getType());
        $this->assertSame($user, $token->getUser());
        $this->assertSame($createdAt, $token->getCreatedAt());
        $this->assertSame($expirationDate, $token->getExpirationDate());
        $this->assertNull($token->getUsedAt());
        $this->assertFalse($token->isUsed());

        $usedAt = new \DateTime('1989-08-20');
        $token->markAsUsed($usedAt);

        $this->assertSame($usedAt, $token->getUsedAt());
        $this->assertTrue($token->isUsed());
    }
}
