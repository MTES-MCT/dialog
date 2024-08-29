<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security;

use App\Application\User\View\UserOrganizationView;
use App\Domain\User\Enum\UserRolesEnum;
use App\Infrastructure\Security\SymfonyUser;
use PHPUnit\Framework\TestCase;

class SymfonyUserTest extends TestCase
{
    public function testUser()
    {
        $organizationUser = new UserOrganizationView('133fb411-7754-4749-9590-ce05a2abe108', 'Mairie de Savenay', []);

        $user = new SymfonyUser(
            '2d3724f1-2910-48b4-ba56-81796f6e100b',
            'mathieu.marchois@beta.gouv.fr',
            'Mathieu MARCHOIS',
            'password',
            [$organizationUser],
            [UserRolesEnum::ROLE_USER->value],
        );

        $this->assertSame('2d3724f1-2910-48b4-ba56-81796f6e100b', $user->getUuid());
        $this->assertSame([UserRolesEnum::ROLE_USER->value], $user->getRoles());
        $this->assertSame('Mathieu MARCHOIS', $user->getFullName());
        $this->assertSame(null, $user->getSalt());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $user->getUsername());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $user->getUserIdentifier());
        $this->assertSame('password', $user->getPassword());
        $this->assertSame([$organizationUser], $user->getUserOrganizations());
        $this->assertSame(['133fb411-7754-4749-9590-ce05a2abe108'], $user->getUserOrganizationUuids());
        $this->assertEmpty($user->eraseCredentials());
    }
}
