<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class OrganizationUserTest extends TestCase
{
    public function testGetters(): void
    {
        $organization = $this->createMock(Organization::class);
        $user = $this->createMock(User::class);

        $organizationUser = (new OrganizationUser('9cebe00d-04d8-48da-89b1-059f6b7bfe44'))
            ->setOrganization($organization)
            ->setUser($user)
            ->setRoles(OrganizationRolesEnum::ROLE_ORGA_ADMIN->value);

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $organizationUser->getUuid());
        $this->assertSame($user, $organizationUser->getUser());
        $this->assertSame($organization, $organizationUser->getOrganization());
        $this->assertSame(OrganizationRolesEnum::ROLE_ORGA_ADMIN->value, $organizationUser->getRoles());
        $this->assertSame(OrganizationRolesEnum::ROLE_ORGA_ADMIN->value, $organizationUser->getRole());
    }
}
