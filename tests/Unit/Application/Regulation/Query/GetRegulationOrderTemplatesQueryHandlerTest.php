<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\Regulation\Query\GetRegulationOrderTemplatesQuery;
use App\Application\Regulation\Query\GetRegulationOrderTemplatesQueryHandler;
use App\Domain\Regulation\DTO\RegulationOrderTemplateDTO;
use App\Domain\Regulation\Repository\RegulationOrderTemplateRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrderTemplatesQueryHandlerTest extends TestCase
{
    public function testGetByOrganization(): void
    {
        $rows = [
            [
                'uuid' => '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                'name' => 'Restriction de vitesse sur route départementale',
            ],
            [
                'uuid' => '3d1c6ec7-28f5-4b6b-be71-b0920e85b4bf',
                'name' => 'Restriction de vitesse sur route nationale',
            ],
            [
                'uuid' => 'ef5b3632-8525-41b5-9e84-3116d9089610',
                'name' => 'Restriction de circulation',
            ],
        ];

        $dto = new RegulationOrderTemplateDTO();
        $dto->organizationUuid = 'dcab837f-4460-4355-99d5-bf4891c35f8f';

        $regulationOrderTemplateRepository = $this->createMock(RegulationOrderTemplateRepositoryInterface::class);

        $regulationOrderTemplateRepository
            ->expects(self::once())
            ->method('findByFilters')
            ->with($dto)
            ->willReturn($rows);

        $handler = new GetRegulationOrderTemplatesQueryHandler($regulationOrderTemplateRepository);
        $regulationOrderTemplates = $handler(new GetRegulationOrderTemplatesQuery($dto));

        $this->assertEquals($rows, $regulationOrderTemplates);
    }
}
