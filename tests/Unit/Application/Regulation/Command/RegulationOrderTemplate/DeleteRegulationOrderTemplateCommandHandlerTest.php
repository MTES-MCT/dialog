<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\RegulationOrderTemplate;

use App\Application\Regulation\Command\RegulationOrderTemplate\DeleteRegulationOrderTemplateCommand;
use App\Application\Regulation\Command\RegulationOrderTemplate\DeleteRegulationOrderTemplateCommandHandler;
use App\Domain\Regulation\Exception\RegulationOrderTemplateCannotBeDeletedException;
use App\Domain\Regulation\Exception\RegulationOrderTemplateNotFoundException;
use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\Regulation\Repository\RegulationOrderTemplateRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeleteRegulationOrderTemplateCommandHandlerTest extends TestCase
{
    private MockObject $regulationOrderTemplateRepository;

    public function setUp(): void
    {
        $this->regulationOrderTemplateRepository = $this->createMock(RegulationOrderTemplateRepositoryInterface::class);
    }

    public function testRemove(): void
    {
        $organization = $this->createMock(Organization::class);
        $regulationOrderTemplate = $this->createMock(RegulationOrderTemplate::class);
        $regulationOrderTemplate
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->regulationOrderTemplateRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('f8216679-5a0b-4dd5-9e2b-b382d298c3b4')
            ->willReturn($regulationOrderTemplate);

        $this->regulationOrderTemplateRepository
            ->expects(self::once())
            ->method('remove')
            ->with($regulationOrderTemplate);

        $handler = new DeleteRegulationOrderTemplateCommandHandler(
            $this->regulationOrderTemplateRepository,
        );
        $command = new DeleteRegulationOrderTemplateCommand('f8216679-5a0b-4dd5-9e2b-b382d298c3b4');

        $handler($command);
    }

    public function testCannotBeDeleted(): void
    {
        $this->expectException(RegulationOrderTemplateCannotBeDeletedException::class);

        $regulationOrderTemplate = $this->createMock(RegulationOrderTemplate::class);
        $regulationOrderTemplate
            ->expects(self::once())
            ->method('getOrganization')
            ->willReturn(null);

        $this->regulationOrderTemplateRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('f8216679-5a0b-4dd5-9e2b-b382d298c3b4')
            ->willReturn($regulationOrderTemplate);

        $this->regulationOrderTemplateRepository
            ->expects(self::never())
            ->method('remove');

        $handler = new DeleteRegulationOrderTemplateCommandHandler(
            $this->regulationOrderTemplateRepository,
        );
        $command = new DeleteRegulationOrderTemplateCommand('f8216679-5a0b-4dd5-9e2b-b382d298c3b4');

        $handler($command);
    }

    public function testNotFound(): void
    {
        $this->expectException(RegulationOrderTemplateNotFoundException::class);

        $this->regulationOrderTemplateRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('f8216679-5a0b-4dd5-9e2b-b382d298c3b4')
            ->willReturn(null);

        $this->regulationOrderTemplateRepository
            ->expects(self::never())
            ->method('remove');

        $handler = new DeleteRegulationOrderTemplateCommandHandler(
            $this->regulationOrderTemplateRepository,
        );
        $command = new DeleteRegulationOrderTemplateCommand('f8216679-5a0b-4dd5-9e2b-b382d298c3b4');

        $handler($command);
    }
}
