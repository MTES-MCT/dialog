<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Specification;

use App\Application\User\View\UserOrganizationView;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Organization;
use App\Domain\User\Specification\CanUserPublishRegulation;
use App\Infrastructure\Security\User\AbstractAuthenticatedUser;
use PHPUnit\Framework\TestCase;

final class CanUserPublishRegulationTest extends TestCase
{
    public function testCanPublish(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('c1790745-b915-4fb5-96e7-79b104092a55');

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $abstractSymfonyUser = $this->createMock(AbstractAuthenticatedUser::class);
        $abstractSymfonyUser
            ->expects(self::once())
            ->method('getUserOrganizations')
            ->willReturn([
                new UserOrganizationView('c1790745-b915-4fb5-96e7-79b104092a55', 'DiaLog', true, [OrganizationRolesEnum::ROLE_ORGA_PUBLISHER->value]),
            ]);

        $pattern = new CanUserPublishRegulation();
        $this->assertTrue($pattern->isSatisfiedBy($regulationOrderRecord, $abstractSymfonyUser));
    }

    public function testCannotPublish(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('c1790745-b915-4fb5-96e7-79b104092a55');

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $abstractSymfonyUser = $this->createMock(AbstractAuthenticatedUser::class);
        $abstractSymfonyUser
            ->expects(self::once())
            ->method('getUserOrganizations')
            ->willReturn([
                new UserOrganizationView('c1790745-b915-4fb5-96e7-79b104092a55', 'DiaLog', true, [OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value]),
            ]);

        $pattern = new CanUserPublishRegulation();
        $this->assertFalse($pattern->isSatisfiedBy($regulationOrderRecord, $abstractSymfonyUser));
    }
}
