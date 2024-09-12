<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Visa\Query;

use App\Application\Visa\Query\GetOrganizationVisaModelsQuery;
use App\Application\Visa\Query\GetOrganizationVisaModelsQueryHandler;
use App\Application\Visa\View\VisaModelView;
use App\Domain\Visa\Repository\VisaModelRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetOrganizationVisaModelsQueryHandlerTest extends TestCase
{
    public function testGet(): void
    {
        $visa1 = new VisaModelView(
            '42a1888f-29cb-4e32-a02f-49d278b6d128',
            'Interdiction de circulation',
            'Circulation interdite dans toute l\'agglomération',
        );

        $visa2 = new VisaModelView(
            '42a1888f-29cb-4e32-a02f-49d278b6d128',
            'Interdiction de circulation',
            'Circulation interdite dans toute l\'agglomération',
        );

        $visaModelRepository = $this->createMock(VisaModelRepositoryInterface::class);
        $visaModelRepository
            ->expects(self::once())
            ->method('findOrganizationVisaModels')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn([$visa1, $visa2]);

        $handler = new GetOrganizationVisaModelsQueryHandler($visaModelRepository);
        $result = $handler(new GetOrganizationVisaModelsQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $expectedResults = [$visa1, $visa2];

        $this->assertEquals($expectedResults, $result);
    }
}
