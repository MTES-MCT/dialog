<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Specification;

use App\Domain\Regulation\Specification\CanOrganizationInterveneOnGeometry;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CanOrganizationInterveneOnGeometryTest extends TestCase
{
    private $organizationRepository;

    public function setUp(): void
    {
        $this->organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
    }

    public function testOrganizationCanInterveneOnGeometry(): void
    {
        $linear = 'LINESTRING(1 1, 2 2, 3 3)';
        $organization = $this->createMock(Organization::class);

        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('62c8c28c-d87b-4cc3-a5c6-6b2c0bc46504')
            ->willReturn($organization);

        $this->organizationRepository
            ->expects(self::once())
            ->method('canInterveneOnGeometry')
            ->with('62c8c28c-d87b-4cc3-a5c6-6b2c0bc46504', $linear)
            ->willReturn(true);

        $specification = new CanOrganizationInterveneOnGeometry($this->organizationRepository);
        $this->assertTrue($specification->isSatisfiedBy('62c8c28c-d87b-4cc3-a5c6-6b2c0bc46504', $linear));
    }

    public function testOrganizationNotFound(): void
    {
        $linear = 'LINESTRING(1 1, 2 2, 3 3)';
        $this->organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('62c8c28c-d87b-4cc3-a5c6-6b2c0bc46504')
            ->willReturn(null);

        $this->organizationRepository
            ->expects(self::never())
            ->method('canInterveneOnGeometry');

        $specification = new CanOrganizationInterveneOnGeometry($this->organizationRepository);
        $this->assertFalse($specification->isSatisfiedBy('62c8c28c-d87b-4cc3-a5c6-6b2c0bc46504', $linear));
    }
}
