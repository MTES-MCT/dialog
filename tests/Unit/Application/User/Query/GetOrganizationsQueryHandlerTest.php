<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Query;

use App\Application\User\Query\GetOrganizationsQuery;
use App\Application\User\Query\GetOrganizationsQueryHandler;
use App\Application\User\View\OrganizationView;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetOrganizationsQueryHandlerTest extends TestCase
{
    public function testGet(): void
    {
        $org1 = $this->createMock(OrganizationView::class);
        $org2 = $this->createMock(OrganizationView::class);

        $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $organizationRepository
            ->expects(self::once())
            ->method('findAll')
            ->with()
            ->willReturn([$org1, $org2]);

        $handler = new GetOrganizationsQueryHandler($organizationRepository);
        $result = $handler(new GetOrganizationsQuery());

        $this->assertEquals([$org1, $org2], $result);
    }
}
