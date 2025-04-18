<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\PasswordUser;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testGetters(): void
    {
        $passwordUser = $this->createMock(PasswordUser::class);
        $date = new \DateTime('2024-05-07');

        $user = (new User('9cebe00d-04d8-48da-89b1-059f6b7bfe44'))
            ->setFullName('Mathieu Marchois')
            ->setEmail('mathieu@fairness.coop')
            ->setRoles([UserRolesEnum::ROLE_SUPER_ADMIN->value]);

        $user->setPasswordUser($passwordUser);
        $user->setRegistrationDate($date);

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $user->getUuid());
        $this->assertSame('Mathieu Marchois', $user->getFullName());
        $this->assertSame('mathieu@fairness.coop', $user->getEmail());
        $this->assertSame([UserRolesEnum::ROLE_SUPER_ADMIN->value], $user->getRoles());
        $this->assertSame($date, $user->getRegistrationDate());
        $this->assertNull($user->getProConnectUser()); // Manage by Doctrine
        $this->assertEquals($passwordUser, $user->getPasswordUser());
        $this->assertSame('Mathieu Marchois (mathieu@fairness.coop)', (string) $user);
        $this->assertFalse($user->isVerified());

        $user->setVerified();
        $this->assertTrue($user->isVerified());
    }
}
