<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\RegulationOrderTemplate;

use App\Application\CommandBusInterface;
use App\Application\Regulation\Command\RegulationOrderTemplate\DuplicateRegulationOrderTemplateCommand;
use App\Application\Regulation\Command\RegulationOrderTemplate\DuplicateRegulationOrderTemplateCommandHandler;
use App\Application\Regulation\Command\RegulationOrderTemplate\SaveRegulationOrderTemplateCommand;
use App\Domain\Regulation\Exception\RegulationOrderTemplateNotFoundException;
use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\Regulation\Repository\RegulationOrderTemplateRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class DuplicateRegulationOrderTemplateCommandHandlerTest extends TestCase
{
    private RegulationOrderTemplateRepositoryInterface $regulationOrderTemplateRepository;
    private CommandBusInterface $commandBus;

    public function setUp(): void
    {
        $this->regulationOrderTemplateRepository = $this->createMock(RegulationOrderTemplateRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
    }

    public function testDuplicate(): void
    {
        $organization = $this->createMock(Organization::class);
        $originalTemplate = $this->createMock(RegulationOrderTemplate::class);

        $originalTemplate
            ->expects(self::once())
            ->method('getName')
            ->willReturn('Template original');

        $originalTemplate
            ->expects(self::once())
            ->method('getTitle')
            ->willReturn('Titre original');

        $originalTemplate
            ->expects(self::once())
            ->method('getVisaContent')
            ->willReturn('Contenu visa original');

        $originalTemplate
            ->expects(self::once())
            ->method('getConsideringContent')
            ->willReturn('Contenu considérant original');

        $originalTemplate
            ->expects(self::once())
            ->method('getArticleContent')
            ->willReturn('Contenu article original');

        $this->regulationOrderTemplateRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('f8216679-5a0b-4dd5-9e2b-b382d298c3b4')
            ->willReturn($originalTemplate);

        $expectedCommand = new SaveRegulationOrderTemplateCommand($organization);
        $expectedCommand->name = 'Template original (copie)';
        $expectedCommand->title = 'Titre original';
        $expectedCommand->visaContent = 'Contenu visa original';
        $expectedCommand->consideringContent = 'Contenu considérant original';
        $expectedCommand->articleContent = 'Contenu article original';

        $this->commandBus
            ->expects(self::once())
            ->method('handle')
            ->with($expectedCommand);

        $handler = new DuplicateRegulationOrderTemplateCommandHandler(
            $this->regulationOrderTemplateRepository,
            $this->commandBus,
        );

        $command = new DuplicateRegulationOrderTemplateCommand($organization, 'f8216679-5a0b-4dd5-9e2b-b382d298c3b4');
        $handler($command);
    }

    public function testTemplateNotFound(): void
    {
        $this->expectException(RegulationOrderTemplateNotFoundException::class);

        $organization = $this->createMock(Organization::class);

        $this->regulationOrderTemplateRepository
            ->expects(self::once())
            ->method('findOneByUuid')
            ->with('f8216679-5a0b-4dd5-9e2b-b382d298c3b4')
            ->willReturn(null);

        $this->commandBus
            ->expects(self::never())
            ->method('handle');

        $handler = new DuplicateRegulationOrderTemplateCommandHandler(
            $this->regulationOrderTemplateRepository,
            $this->commandBus,
        );

        $command = new DuplicateRegulationOrderTemplateCommand($organization, 'f8216679-5a0b-4dd5-9e2b-b382d298c3b4');
        $handler($command);
    }
}
