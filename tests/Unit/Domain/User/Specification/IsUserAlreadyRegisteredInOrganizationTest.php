<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Specification;

use App\Domain\User\Organization;
use App\Domain\User\OrganizationUser;
use App\Domain\User\Repository\OrganizationUserRepositoryInterface;
use App\Domain\User\Specification\IsUserAlreadyRegisteredInOrganization;
use PHPUnit\Framework\TestCase;

final class IsUserAlreadyRegisteredInOrganizationTest extends TestCase
{
    public function testUserAlreadyExist(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('97ebfcee-80dc-4bdc-bcde-681e54c4d717');

        $organizationUser = $this->createMock(OrganizationUser::class);
        $organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $organizationUserRepository
            ->expects(self::once())
            ->method('findByEmailAndOrganization')
            ->with('mathieu.marchois@beta.gouv.fr', '97ebfcee-80dc-4bdc-bcde-681e54c4d717')
            ->willReturn($organizationUser);

        $pattern = new IsUserAlreadyRegisteredInOrganization($organizationUserRepository);
        $this->assertTrue($pattern->isSatisfiedBy('mathieu.marchois@beta.gouv.fr', $organization));
    }

    public function testUserDoesntExist(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('97ebfcee-80dc-4bdc-bcde-681e54c4d717');

        $organizationUserRepository = $this->createMock(OrganizationUserRepositoryInterface::class);
        $organizationUserRepository
            ->expects(self::once())
            ->method('findByEmailAndOrganization')
            ->with('mathieu.marchois@beta.gouv.fr', '97ebfcee-80dc-4bdc-bcde-681e54c4d717')
            ->willReturn(null);

        $pattern = new IsUserAlreadyRegisteredInOrganization($organizationUserRepository);
        $this->assertFalse($pattern->isSatisfiedBy('mathieu.marchois@beta.gouv.fr', $organization));
    }
}
