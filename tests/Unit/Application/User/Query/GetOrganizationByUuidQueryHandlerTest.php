<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Query;

use App\Application\User\Query\GetOrganizationByUuidQuery;
use App\Application\User\Query\GetOrganizationByUuidQueryHandler;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetOrganizationByUuidQueryHandlerTest extends TestCase
{
    public function testGetOne(): void
    {
        $organization = $this->createMock(Organization::class);
        $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn($organization);

        $handler = new GetOrganizationByUuidQueryHandler($organizationRepository);
        $result = $handler(new GetOrganizationByUuidQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($organization, $result);
    }

    public function testNoOrganization(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $organizationRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn(null);

        $handler = new GetOrganizationByUuidQueryHandler($organizationRepository);
        $handler(new GetOrganizationByUuidQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));
    }
}
