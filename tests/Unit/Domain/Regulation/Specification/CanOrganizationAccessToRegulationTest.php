<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Specification;

use App\Domain\Regulation\Specification\CanOrganizationAccessToRegulation;
use App\Domain\User\Organization;
use App\Domain\User\OrganizationRegulationAccessInterface;
use PHPUnit\Framework\TestCase;

final class CanOrganizationAccessToRegulationTest extends TestCase
{
    public function testRegulationCanBePublished(): void
    {
        $userOrganization = $this->createMock(Organization::class);
        $userOrganization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('f35dc505-50a9-40ac-8bff-e0dff961aaf8');

        $organization = $this->createMock(OrganizationRegulationAccessInterface::class);
        $organization
            ->expects(self::once())
            ->method('getOrganizationUuid')
            ->willReturn('f35dc505-50a9-40ac-8bff-e0dff961aaf8');

        $specification = new CanOrganizationAccessToRegulation();
        $this->assertTrue($specification->isSatisfiedBy($organization, $userOrganization));
    }

    public function testRegulationCannotBePublished(): void
    {
        $otherOrganization = $this->createMock(OrganizationRegulationAccessInterface::class);
        $otherOrganization
            ->expects(self::once())
            ->method('getOrganizationUuid')
            ->willReturn('f10d60a0-2431-4de5-bb54-1ef6da0df671');

        $userOrganization = $this->createMock(Organization::class);
        $userOrganization
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('f35dc505-50a9-40ac-8bff-e0dff961aaf8');

        $specification = new CanOrganizationAccessToRegulation();
        $this->assertFalse($specification->isSatisfiedBy($otherOrganization, $userOrganization));
    }
}
