<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\PasswordUser;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class PasswordUserTest extends TestCase
{
    public function testGetters(): void
    {
        $user = $this->createMock(User::class);
        $passwordUser = new PasswordUser('9cebe00d-04d8-48da-89b1-059f6b7bfe44', 'password', $user);

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $passwordUser->getUuid());
        $this->assertSame('password', $passwordUser->getPassword());
        $this->assertSame($user, $passwordUser->getUser());
    }
}
