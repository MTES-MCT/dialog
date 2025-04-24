<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\RegulationOrderTemplate;

use App\Application\Regulation\Query\RegulationOrderTemplate\GetRegulationOrderTemplateQuery;
use App\Application\Regulation\Query\RegulationOrderTemplate\GetRegulationOrderTemplateQueryHandler;
use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\Regulation\Repository\RegulationOrderTemplateRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrderTemplateQueryHandlerTest extends TestCase
{
    private RegulationOrderTemplateRepositoryInterface $repository;
    private GetRegulationOrderTemplateQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RegulationOrderTemplateRepositoryInterface::class);
        $this->handler = new GetRegulationOrderTemplateQueryHandler($this->repository);
    }

    public function testGetRegulationOrderTemplate(): void
    {
        $query = new GetRegulationOrderTemplateQuery('29488f0c-7b16-4d6c-82e3-f395102c32c2');
        $template = $this->createMock(RegulationOrderTemplate::class);

        $this->repository
            ->expects($this->once())
            ->method('findOneByUuid')
            ->with('29488f0c-7b16-4d6c-82e3-f395102c32c2')
            ->willReturn($template);

        $result = ($this->handler)($query);

        $this->assertSame($template, $result);
    }
}
