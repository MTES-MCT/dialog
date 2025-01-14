<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testGetters(): void
    {
        $date = new \DateTime('2024-05-07');

        $user = (new User('9cebe00d-04d8-48da-89b1-059f6b7bfe44'))
            ->setFullName('Mathieu Marchois')
            ->setEmail('mathieu@fairness.coop')
            ->setRoles([UserRolesEnum::ROLE_SUPER_ADMIN->value]);

        $user->setRegistrationDate($date);

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $user->getUuid());
        $this->assertSame('Mathieu Marchois', $user->getFullName());
        $this->assertSame('mathieu@fairness.coop', $user->getEmail());
        $this->assertSame([UserRolesEnum::ROLE_SUPER_ADMIN->value], $user->getRoles());
        $this->assertSame($date, $user->getRegistrationDate());
        $this->assertNull($user->getProConnectUser()); // Manage by Doctrine
        $this->assertNull($user->getPasswordUser()); // Manage by Doctrine
        $this->assertSame('Mathieu Marchois (mathieu@fairness.coop)', (string) $user);
    }
}
