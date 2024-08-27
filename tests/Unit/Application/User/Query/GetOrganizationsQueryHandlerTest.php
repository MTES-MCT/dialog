<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Query;

use App\Application\User\Query\GetOrganizationsQuery;
use App\Application\User\Query\GetOrganizationsQueryHandler;
use App\Application\User\View\OrganizationView;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetOrganizationsQueryHandlerTest extends TestCase
{
    public function testGet(): void
    {
        $org1 = $this->createMock(Organization::class);
        $org1
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('066cf16c-da0c-7d87-8000-84725a9494d6');
        $org1
            ->expects(self::once())
            ->method('getName')
            ->willReturn('Org 1');

        $org2 = $this->createMock(Organization::class);
        $org2
            ->expects(self::once())
            ->method('getUuid')
            ->willReturn('066cf16e-b960-7e92-8000-fc6c965e97e6');
        $org2
            ->expects(self::once())
            ->method('getName')
            ->willReturn('Org 2');

        $expectedResults = [
            new OrganizationView('066cf16c-da0c-7d87-8000-84725a9494d6', 'Org 1'),
            new OrganizationView('066cf16e-b960-7e92-8000-fc6c965e97e6', 'Org 2'),
        ];

        $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $organizationRepository
            ->expects(self::once())
            ->method('findAll')
            ->with()
            ->willReturn([$org1, $org2]);

        $handler = new GetOrganizationsQueryHandler($organizationRepository);
        $result = $handler(new GetOrganizationsQuery());

        $this->assertEquals($expectedResults, $result);
    }
}
