<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\ProConnectUser;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class ProConnectUserTest extends TestCase
{
    public function testGetters(): void
    {
        $user = $this->createMock(User::class);
        $proConnectUser = new ProConnectUser('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $user);

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $proConnectUser->getUuid());
        $this->assertSame($user, $proConnectUser->getUser());
    }
}
