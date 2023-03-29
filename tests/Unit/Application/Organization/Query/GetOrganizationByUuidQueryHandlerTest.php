<?php

namespace App\Tests\Unit\Application\Organization\Query;

use App\Application\Organization\Query\GetOrganizationByUuidQuery;
use App\Application\Organization\Query\GetOrganizationByUuidQueryHandler;
use App\Domain\Organization\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

class GetOrganizationByUuidQueryHandlerTest extends TestCase
{
    function testGetOne(){
        $organization = $this->createMock(Organization::class);
        $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $organizationRepository
            ->expects(self::once())
            ->method('findByUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn($organization);

        $handler = new GetOrganizationByUuidQueryHandler($organizationRepository);
        $result = $handler->__invoke(new GetOrganizationByUuidQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($organization,$result);
    }
}