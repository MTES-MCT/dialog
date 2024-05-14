<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetDuplicateIdentifierQuery;
use App\Application\Regulation\Query\GetDuplicateIdentifierQueryHandler;
use App\Domain\Regulation\Repository\RegulationOrderRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class GetDuplicateIdentifierQueryHandlerTest extends TestCase
{
    public function testHandler(): void
    {
        $organization = $this->createMock(Organization::class);
        $regulationOrderRepository = $this->createMock(RegulationOrderRepositoryInterface::class);

        $regulationOrderRepository
            ->expects(self::once())
            ->method('getDuplicateIdentifier')
            ->with('identifier', $organization)
            ->willReturn('duplicateIdentifier');

        $handler = new GetDuplicateIdentifierQueryHandler($regulationOrderRepository);
        $result = $handler(new GetDuplicateIdentifierQuery('identifier', $organization));

        $this->assertEquals('duplicateIdentifier', $result);
    }
}
