<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Specification;

use App\Domain\User\Organization;
use App\Domain\User\Specification\CanUserViewOrganization;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use PHPUnit\Framework\TestCase;

final class CanUserViewOrganizationTest extends TestCase
{
    public function testCanView(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('c1790745-b915-4fb5-96e7-79b104092a55');

        $abstractSymfonyUser = $this->createMock(AbstractAuthenticatedUser::class);
        $abstractSymfonyUser
            ->expects(self::once())
            ->method('getUserOrganizationUuids')
            ->willReturn(['c1790745-b915-4fb5-96e7-79b104092a55']);

        $pattern = new CanUserViewOrganization();
        $this->assertTrue($pattern->isSatisfiedBy($organization, $abstractSymfonyUser));
    }

    public function testCannotView(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('28aaef2a-e9d1-4189-9ead-17e866a8726f');

        $abstractSymfonyUser = $this->createMock(AbstractAuthenticatedUser::class);
        $abstractSymfonyUser
            ->expects(self::once())
            ->method('getUserOrganizationUuids')
            ->willReturn(['c1790745-b915-4fb5-96e7-79b104092a55']);

        $pattern = new CanUserViewOrganization();
        $this->assertFalse($pattern->isSatisfiedBy($organization, $abstractSymfonyUser));
    }
}
