<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Organization\VisaModel\Query;

use App\Application\Organization\VisaModel\Query\GetVisaModelQuery;
use App\Application\Organization\VisaModel\Query\GetVisaModelQueryHandler;
use App\Domain\Organization\VisaModel\Exception\VisaModelNotFoundException;
use App\Domain\Organization\VisaModel\Repository\VisaModelRepositoryInterface;
use App\Domain\Organization\VisaModel\VisaModel;
use PHPUnit\Framework\TestCase;

final class GetVisaModelQueryHandlerTest extends TestCase
{
    public function testGet(): void
    {
        $visaModel = $this->createMock(VisaModel::class);

        $visaModelRepository = $this->createMock(VisaModelRepositoryInterface::class);
        $visaModelRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn($visaModel);

        $handler = new GetVisaModelQueryHandler($visaModelRepository);
        $result = $handler(new GetVisaModelQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $this->assertEquals($visaModel, $result);
    }

    public function testVisaModelNotFound(): void
    {
        $this->expectException(VisaModelNotFoundException::class);

        $visaModelRepository = $this->createMock(VisaModelRepositoryInterface::class);
        $visaModelRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn(null);

        $handler = new GetVisaModelQueryHandler($visaModelRepository);
        $handler(new GetVisaModelQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));
    }
}
