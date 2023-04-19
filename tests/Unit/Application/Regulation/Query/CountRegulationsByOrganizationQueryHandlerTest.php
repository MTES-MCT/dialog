<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\CountRegulationsByOrganizationQuery;
use App\Application\Regulation\Query\CountRegulationsByOrganizationQueryHandler;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class CountRegulationsByOrganizationQueryHandlerTest extends TestCase
{
    public function testCount(): void
    {
        $organization = $this->createMock(Organization::class);
        $regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);

        $regulationOrderRecordRepository
            ->expects(self::once())
            ->method('countRegulationsByOrganization')
            ->with($organization, true)
            ->willReturn(2);

        $handler = new CountRegulationsByOrganizationQueryHandler($regulationOrderRecordRepository);
        $regulationOrders = $handler(new CountRegulationsByOrganizationQuery($organization, true));

        $this->assertSame(2, $regulationOrders);
    }
}
