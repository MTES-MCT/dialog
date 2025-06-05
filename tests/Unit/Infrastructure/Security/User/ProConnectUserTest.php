<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security\User;

use App\Application\User\View\UserOrganizationView;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\User;
use App\Infrastructure\Security\User\ProConnectUser;
use PHPUnit\Framework\TestCase;

class ProConnectUserTest extends TestCase
{
    public function testUser()
    {
        $organizationUser = new UserOrganizationView('133fb411-7754-4749-9590-ce05a2abe108', 'Mairie de Savenay', true, []);

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getUuid')->willReturn('2d3724f1-2910-48b4-ba56-81796f6e100b');
        $user->expects(self::once())->method('getEmail')->willReturn('mathieu.marchois@beta.gouv.fr');
        $user->expects(self::once())->method('getFullName')->willReturn('Mathieu MARCHOIS');
        $user->expects(self::once())->method('getRoles')->willReturn([UserRolesEnum::ROLE_USER->value]);

        $passwordUser = new ProConnectUser($user, [$organizationUser]);

        $this->assertSame('2d3724f1-2910-48b4-ba56-81796f6e100b', $passwordUser->getUuid());
        $this->assertSame([UserRolesEnum::ROLE_USER->value], $passwordUser->getRoles());
        $this->assertSame('Mathieu MARCHOIS', $passwordUser->getFullName());
        $this->assertSame(null, $passwordUser->getSalt());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $passwordUser->getUsername());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $passwordUser->getUserIdentifier());
        $this->assertSame([$organizationUser], $passwordUser->getUserOrganizations());
        $this->assertSame(['133fb411-7754-4749-9590-ce05a2abe108'], $passwordUser->getUserOrganizationUuids());
        $this->assertEmpty($passwordUser->eraseCredentials());
        $this->assertTrue($passwordUser->isVerified());
        $this->assertSame('proconnect', $passwordUser->getAuthOrigin());
    }
}
