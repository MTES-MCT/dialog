<?php

namespace App\Tests\Unit\Application\Organization\Query;

use App\Application\Organization\Query\GetOrganizationsQuery;
use App\Application\Organization\Query\GetOrganizationsQueryHandler;
use App\Application\Organization\View\OrganizationListView;
use App\Domain\Organization\Repository\OrganizationRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

class GetOrganizationsQueryHandlerTest extends TestCase
{
    function testgetOrganizations(){
        // $organization = $this->createMock(Organization::class);
        // $organization2 = $this->createMock(Organization::class);
        // $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        // $organizationRepository
        // ->expects(self::once())
        // ->method('findOrganizations')
        // ->willReturn([$organization, $organization2]);
        $organization = $this->createMock(Organization::class);
        $organization2 = $this->createMock(Organization::class);
        $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $organizationRepository
        ->expects(self::once())
        ->method('findOrganizations')
        ->willReturn([$organization, $organization2]);
        $organization
        ->expects(self::once())
        ->method('getUuid')
        ->willReturn('2229f88c-6480-4a93-a498-1d384d4256eb');
        $organization
        ->expects(self::once())
        ->method('getName')
        ->willReturn('Apouet bis');

        $organization2
        ->expects(self::once())
        ->method('getName')
        ->willReturn('DiaLog');
        $organization2
        ->expects(self::once())
        ->method('getUuid')
        ->willReturn('e0d93630-acf7-4722-81e8-ff7d5fa64b66');


        $organizationListView = [
            new OrganizationListView('2229f88c-6480-4a93-a498-1d384d4256eb','Apouet bis'),new OrganizationListView('e0d93630-acf7-4722-81e8-ff7d5fa64b66','DiaLog')
        ];
        $handler = new GetOrganizationsQueryHandler($organizationRepository);
        $result = $handler->__invoke(new GetOrganizationsQuery());
        $this->assertEquals($organizationListView,$result);
        // $this->assertEquals([$organization,$organization2],$result);
    }
}