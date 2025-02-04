<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User;

use App\Domain\User\Enum\OrganizationRolesEnum;
use App\Domain\User\Invitation;
use App\Domain\User\Organization;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class InvitationTest extends TestCase
{
    public function testGetters(): void
    {
        $owner = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);
        $createdAt = new \DateTime('2025-01-17');
        $invitation = new Invitation(
            '9cebe00d-04d8-48da-89b1-059f6b7bfe44',
            'mathieu@fairness.coop',
            'Mathieu MARCHOIS',
            OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value,
            $createdAt,
            $owner,
            $organization,
        );

        $this->assertSame('9cebe00d-04d8-48da-89b1-059f6b7bfe44', $invitation->getUuid());
        $this->assertSame('mathieu@fairness.coop', $invitation->getEmail());
        $this->assertSame('Mathieu MARCHOIS', $invitation->getFullName());
        $this->assertSame(OrganizationRolesEnum::ROLE_ORGA_CONTRIBUTOR->value, $invitation->getRole());
        $this->assertSame($owner, $invitation->getOwner());
        $this->assertSame($organization, $invitation->getOrganization());
        $this->assertSame($createdAt, $invitation->getCreatedAt());
    }
}
