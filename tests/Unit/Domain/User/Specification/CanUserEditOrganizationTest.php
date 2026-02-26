<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Specification;

use App\Domain\User\Organization;
use App\Domain\User\Specification\CanUserEditOrganization;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use PHPUnit\Framework\TestCase;

final class CanUserEditOrganizationTest extends TestCase
{
    public function testCanEdit(): void
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

        $pattern = new CanUserEditOrganization();
        $this->assertTrue($pattern->isSatisfiedBy($organization, $abstractSymfonyUser));
    }

    public function testCannotEdit(): void
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
            ->willReturn(['other-uuid']);

        $pattern = new CanUserEditOrganization();
        $this->assertFalse($pattern->isSatisfiedBy($organization, $abstractSymfonyUser));
    }
}
