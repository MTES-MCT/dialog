<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Security;

use App\Domain\User\Organization;
use App\Infrastructure\Security\SymfonyUser;
use PHPUnit\Framework\TestCase;

class SymfonyUserTest extends TestCase
{
    public function testUser()
    {
        $organization = (new Organization('133fb411-7754-4749-9590-ce05a2abe108'))
            ->setName('Mairie de Saint-Ouen Sur Seine');

        $organizations = [$organization];

        $user = new SymfonyUser(
            '2d3724f1-2910-48b4-ba56-81796f6e100b',
            'mathieu.marchois@beta.gouv.fr',
            'Mathieu MARCHOIS',
            'password',
            $organizations,
            ['ROLE_USER'],
        );

        $this->assertSame('2d3724f1-2910-48b4-ba56-81796f6e100b', $user->getUuid());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
        $this->assertSame('Mathieu MARCHOIS', $user->getFullName());
        $this->assertSame(null, $user->getSalt());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $user->getUsername());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $user->getUserIdentifier());
        $this->assertSame('password', $user->getPassword());
        $this->assertSame($organizations, $user->getOrganizations());
        $this->assertSame(['133fb411-7754-4749-9590-ce05a2abe108'], $user->getOrganizationUuids());
        $this->assertEmpty($user->eraseCredentials());
    }
}
