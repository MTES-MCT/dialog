<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\VisaModel\Query;

use App\Application\VisaModel\Query\GetOrganizationVisaModelsQuery;
use App\Application\VisaModel\Query\GetOrganizationVisaModelsQueryHandler;
use App\Application\VisaModel\View\VisaModelView;
use App\Domain\VisaModel\Repository\VisaModelRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetOrganizationVisaModelsQueryHandlerTest extends TestCase
{
    public function testGet(): void
    {
        $visaModel1 = new VisaModelView(
            '42a1888f-29cb-4e32-a02f-49d278b6d128',
            'Interdiction de circulation',
            'Circulation interdite dans toute l\'agglomération',
            '0b8d5e82-536d-4de3-a0e8-a17c99748724',
            'DiaLog',
        );

        $visaModel2 = new VisaModelView(
            '42a1888f-29cb-4e32-a02f-49d278b6d128',
            'Interdiction de circulation',
            'Circulation interdite dans toute l\'agglomération',
            '0b8d5e82-536d-4de3-a0e8-a17c99748724',
            'Mairie de Savenay',
        );

        $visaModelRepository = $this->createMock(VisaModelRepositoryInterface::class);
        $visaModelRepository
            ->expects(self::once())
            ->method('findOrganizationVisaModels')
            ->with('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf')
            ->willReturn([$visaModel1, $visaModel2]);

        $handler = new GetOrganizationVisaModelsQueryHandler($visaModelRepository);
        $result = $handler(new GetOrganizationVisaModelsQuery('3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf'));

        $expectedResults = [$visaModel1, $visaModel2];

        $this->assertEquals($expectedResults, $result);
    }
}
