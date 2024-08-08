<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Specification;

use App\Domain\User\Organization;
use App\Domain\User\Specification\CanUserViewOrganization;
use App\Infrastructure\Security\SymfonyUser;
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

        $symfonyUser = $this->createMock(SymfonyUser::class);
        $symfonyUser
            ->expects(self::once())
            ->method('getOrganizationUuids')
            ->willReturn(['c1790745-b915-4fb5-96e7-79b104092a55']);

        $pattern = new CanUserViewOrganization();
        $this->assertTrue($pattern->isSatisfiedBy($organization, $symfonyUser));
    }

    public function testCannotView(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('28aaef2a-e9d1-4189-9ead-17e866a8726f');

        $symfonyUser = $this->createMock(SymfonyUser::class);
        $symfonyUser
            ->expects(self::once())
            ->method('getOrganizationUuids')
            ->willReturn(['c1790745-b915-4fb5-96e7-79b104092a55']);

        $pattern = new CanUserViewOrganization();
        $this->assertFalse($pattern->isSatisfiedBy($organization, $symfonyUser));
    }
}
