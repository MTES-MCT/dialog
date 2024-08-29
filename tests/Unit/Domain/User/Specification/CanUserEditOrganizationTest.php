<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Specification;

use App\Application\User\View\UserOrganizationView;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Organization;
use App\Domain\User\Specification\CanUserEditOrganization;
use App\Infrastructure\Security\SymfonyUser;
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

        $symfonyUser = $this->createMock(SymfonyUser::class);
        $symfonyUser
            ->expects(self::once())
            ->method('getUserOrganizations')
            ->willReturn([
                new UserOrganizationView('c1790745-b915-4fb5-96e7-79b104092a55', 'Dialog', [OrganizationRolesEnum::ROLE_ORGA_ADMIN->value]),
            ]);

        $pattern = new CanUserEditOrganization();
        $this->assertTrue($pattern->isSatisfiedBy($organization, $symfonyUser));
    }

    public function testCannotEdit(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('c1790745-b915-4fb5-96e7-79b104092a55');

        $symfonyUser = $this->createMock(SymfonyUser::class);
        $symfonyUser
            ->expects(self::once())
            ->method('getUserOrganizations')
            ->willReturn([
                new UserOrganizationView('c1790745-b915-4fb5-96e7-79b104092a55', 'Dialog', [OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value]),
            ]);

        $pattern = new CanUserEditOrganization();
        $this->assertFalse($pattern->isSatisfiedBy($organization, $symfonyUser));
    }
}
