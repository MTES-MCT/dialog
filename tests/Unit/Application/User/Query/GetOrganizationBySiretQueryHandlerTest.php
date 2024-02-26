<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\User\Query;

use App\Application\User\Query\GetOrganizationBySiretQuery;
use App\Application\User\Query\GetOrganizationBySiretQueryHandler;
use App\Domain\User\Exception\OrganizationNotFoundException;
use App\Domain\User\Organization;
use App\Domain\User\Repository\OrganizationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetOrganizationBySiretQueryHandlerTest extends TestCase
{
    public function testGetOne(): void
    {
        $organization = $this->createMock(Organization::class);
        $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $organizationRepository
            ->expects(self::once())
            ->method('findOneBySiret')
            ->with('82050375300015')
            ->willReturn($organization);

        $handler = new GetOrganizationBySiretQueryHandler($organizationRepository);
        $result = $handler(new GetOrganizationBySiretQuery('82050375300015'));

        $this->assertEquals($organization, $result);
    }

    public function testNoOrganization(): void
    {
        $this->expectException(OrganizationNotFoundException::class);

        $organizationRepository = $this->createMock(OrganizationRepositoryInterface::class);
        $organizationRepository
            ->expects(self::once())
            ->method('findOneBySiret')
            ->with('82050375300015')
            ->willReturn(null);

        $handler = new GetOrganizationBySiretQueryHandler($organizationRepository);
        $handler(new GetOrganizationBySiretQuery('82050375300015'));
    }
}
