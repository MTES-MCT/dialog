<?php

namespace App\Tests\Unit\Application\Organization\Query;

use App\Application\Organization\Query\GetOrganizationsQuery;
use App\Application\Organization\Query\GetOrganizationsQueryHandler;
use App\Domain\Organization\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

class GetOrganizationsQueryHandlerTest extends TestCase
{
    function testgetOrganizations(){
        $organization = $this->createMock(Organization::class);
        $organization2 = $this->createMock(Organization::class);
        $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $organizationRepository
        ->expects(self::once())
        ->method('findOrganizations')
        ->willReturn([$organization, $organization2]);

        $handler = new GetOrganizationsQueryHandler($organizationRepository);
        $result = $handler->__invoke(new GetOrganizationsQuery());

        $this->assertEquals([$organization,$organization2],$result);
    }
}