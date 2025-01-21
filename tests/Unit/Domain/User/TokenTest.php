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
        $expirationDate = new \DateTime('1989-09-17');
        $token = new Token(
            '9cebe00d-04d8-48da-89b1-059f6b7bfe44',
            'token',
            TokenTypeEnum::FORGOT_PASSWORD->value,
            $user,
            $expirationDate,
        );

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $token->getUuid());
        $this->assertSame('token', $token->getToken());
        $this->assertSame(TokenTypeEnum::FORGOT_PASSWORD->value, $token->getType());
        $this->assertSame($user, $token->getUser());
        $this->assertSame($expirationDate, $token->getExpirationDate());
    }
}
