<?php

declare(strict_types=1);

namespace App\Tests\Domain\User;

use App\Domain\User\Organization;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testGetters(): void
    {
        $organization1 = $this->createMock(Organization::class);
        $organization1
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('bec265a8-f3ef-4d2e-82f6-76060946020a');

        $organization2 = $this->createMock(Organization::class);
        $organization2
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('dc28230e-7935-4686-905b-68a27b51913d');

        $user = new User(
            '9cebe00d-04d8-48da-89b1-059f6b7bfe44',
            'Mathieu Marchois',
            'mathieu@fairness.coop',
            'password',
            [$organization1, $organization2],
        );

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $user->getUuid());
        $this->assertSame('Mathieu Marchois', $user->getFullName());
        $this->assertSame('mathieu@fairness.coop', $user->getEmail());
        $this->assertSame('password', $user->getPassword());
        $this->assertSame(['bec265a8-f3ef-4d2e-82f6-76060946020a', 'dc28230e-7935-4686-905b-68a27b51913d'], $user->getOrganizationUuids());
    }

    public function testWithoutOrganization(): void
    {
        $user = new User(
            '9cebe00d-04d8-48da-89b1-059f6b7bfe44',
            'Mathieu Marchois',
            'mathieu@fairness.coop',
            'password',
        );

        $this->assertSame([], $user->getOrganizationUuids());
    }
}
