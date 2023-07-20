<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\Organization;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testGetters(): void
    {
        $organization1 = $this->createMock(Organization::class);
        $organization2 = $this->createMock(Organization::class);

        $user = (new User('9cebe00d-04d8-48da-89b1-059f6b7bfe44'))
            ->setFullName('Mathieu Marchois')
            ->setEmail('mathieu@fairness.coop')
            ->setPassword('password');

        $user->addOrganization($organization1);
        $user->addOrganization($organization2);
        $user->addOrganization($organization1); // Test deduplication

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $user->getUuid());
        $this->assertSame('Mathieu Marchois', $user->getFullName());
        $this->assertSame('mathieu@fairness.coop', $user->getEmail());
        $this->assertSame('password', $user->getPassword());
        $this->assertSame([$organization1, $organization2], $user->getOrganizations()->toArray());

        $user->removeOrganization($organization1);
        $user->removeOrganization($organization1); // Test removal of non existing organization
        $this->assertSame([1 => $organization2], $user->getOrganizations()->toArray());
    }

    public function testWithoutOrganization(): void
    {
        $user = (new User('9cebe00d-04d8-48da-89b1-059f6b7bfe44'))
            ->setFullName('Mathieu Marchois')
            ->setEmail('mathieu@fairness.coop')
            ->setPassword('password');

        $this->assertSame([], $user->getOrganizations()->toArray());
    }
}
