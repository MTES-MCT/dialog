<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetOrganizationIdentifiersQuery;
use App\Application\Regulation\Query\GetOrganizationIdentifiersQueryHandler;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class GetOrganizationIdentifiersQueryHandlerTest extends TestCase
{
    public function testHandlerReturnsIdentifiersForOrganization(): void
    {
        $organization = $this->createMock(Organization::class);
        $repository = $this->createMock(RegulationOrderRepositoryInterface::class);

        $repository
            ->expects(self::once())
            ->method('findIdentifiersByOrganization')
            ->with($organization)
            ->willReturn(['ID-001', 'ID-002']);

        $handler = new GetOrganizationIdentifiersQueryHandler($repository);
        $result = $handler(new GetOrganizationIdentifiersQuery($organization));

        $this->assertSame(['ID-001', 'ID-002'], $result);
    }
}
