<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security\User;

use App\Application\User\View\UserOrganizationView;
use App\Domain\User\Enum\UserRolesEnum;
use App\Domain\User\PasswordUser as UserPasswordUser;
use App\Domain\User\User;
use App\Infrastructure\Security\User\PasswordUser;
use PHPUnit\Framework\TestCase;

class PasswordUserTest extends TestCase
{
    public function testUser()
    {
        $organizationUser = new UserOrganizationView('133fb411-7754-4749-9590-ce05a2abe108', 'Mairie de Savenay', true);

        $userPasswordUser = $this->createMock(UserPasswordUser::class);
        $userPasswordUser->expects(self::once())->method('getPassword')->willReturn('password');

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getUuid')->willReturn('2d3724f1-2910-48b4-ba56-81796f6e100b');
        $user->expects(self::once())->method('getEmail')->willReturn('mathieu.marchois@beta.gouv.fr');
        $user->expects(self::once())->method('getFullName')->willReturn('Mathieu MARCHOIS');
        $user->expects(self::once())->method('isVerified')->willReturn(false);
        $user->expects(self::once())->method('getRoles')->willReturn([UserRolesEnum::ROLE_USER->value]);
        $user->expects(self::once())->method('getPasswordUser')->willReturn($userPasswordUser);

        $passwordUser = new PasswordUser($user, [$organizationUser]);

        $this->assertSame('2d3724f1-2910-48b4-ba56-81796f6e100b', $passwordUser->getUuid());
        $this->assertSame([UserRolesEnum::ROLE_USER->value], $passwordUser->getRoles());
        $this->assertSame('Mathieu MARCHOIS', $passwordUser->getFullName());
        $this->assertSame(null, $passwordUser->getSalt());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $passwordUser->getUsername());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $passwordUser->getUserIdentifier());
        $this->assertSame('password', $passwordUser->getPassword());
        $this->assertSame([$organizationUser], $passwordUser->getUserOrganizations());
        $this->assertSame(['133fb411-7754-4749-9590-ce05a2abe108'], $passwordUser->getUserOrganizationUuids());
        $this->assertEmpty($passwordUser->eraseCredentials());
        $this->assertFalse($passwordUser->isVerified());
        $this->assertSame('local', $passwordUser->getAuthOrigin());
        $this->assertTrue($passwordUser->isOrganizationsCompleted());
        $this->assertTrue($passwordUser->isEmailAuthEnabled());
        $this->assertSame('mathieu.marchois@beta.gouv.fr', $passwordUser->getEmailAuthRecipient());
        $this->assertSame(0, $passwordUser->getTrustedTokenVersion());
    }

    public function testEmailAuthCodeIsExposedWhenNotExpired(): void
    {
        $now = new \DateTimeImmutable('2024-01-01 10:00:00');

        $userPasswordUser = $this->createMock(UserPasswordUser::class);
        $userPasswordUser->method('getPassword')->willReturn('password');

        $user = $this->createMock(User::class);
        $user->method('getPasswordUser')->willReturn($userPasswordUser);
        $user->method('getEmailAuthCode')->willReturn('123456');
        $user->method('getEmailAuthCodeExpiresAt')->willReturn(new \DateTimeImmutable('2024-01-01 10:05:00'));

        $passwordUser = new PasswordUser($user, [], $now);

        $this->assertSame('123456', $passwordUser->getEmailAuthCode());
    }

    public function testExpiredEmailAuthCodeIsHiddenAndCanBeReplaced(): void
    {
        $now = new \DateTimeImmutable('2024-01-01 10:06:00');

        $userPasswordUser = $this->createMock(UserPasswordUser::class);
        $userPasswordUser->method('getPassword')->willReturn('password');

        $user = $this->createMock(User::class);
        $user->method('getPasswordUser')->willReturn($userPasswordUser);
        $user->method('getEmailAuthCode')->willReturn('123456');
        $user->method('getEmailAuthCodeExpiresAt')->willReturn(new \DateTimeImmutable('2024-01-01 10:05:00'));

        $passwordUser = new PasswordUser($user, [], $now);

        $this->assertNull($passwordUser->getEmailAuthCode());

        $passwordUser->setEmailAuthCode('654321');
        $this->assertSame('654321', $passwordUser->getEmailAuthCode());
    }
}
