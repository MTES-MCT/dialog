<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\RegulationOrderTemplate;

use App\Application\DateUtilsInterface;
use App\Application\IdFactoryInterface;
use App\Application\Regulation\Command\RegulationOrderTemplate\SaveRegulationOrderTemplateCommand;
use App\Application\Regulation\Command\RegulationOrderTemplate\SaveRegulationOrderTemplateCommandHandler;
use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\Regulation\Repository\RegulationOrderTemplateRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveRegulationOrderTemplateCommandHandlerTest extends TestCase
{
    private MockObject $idFactory;
    private MockObject $regulationOrderTemplateRepository;
    private MockObject $dateUtils;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->regulationOrderTemplateRepository = $this->createMock(RegulationOrderTemplateRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
    }

    public function testAdd(): void
    {
        $organization = $this->createMock(Organization::class);

        $regulationOrderTemplate = new RegulationOrderTemplate(
            uuid: '9cebe00d-04d8-48da-89b1-059f6b7bfe44',
            name: 'Restriction de vitesse',
            title: 'Arrete temporaire n°[numero_arrete]',
            visaContent: 'VU ...',
            consideringContent: 'CONSIDERANT ...',
            articleContent: 'ARTICLES ...',
            createdAt: new \DateTimeImmutable('2025-04-07'),
            organization: $organization,
        );

        $this->regulationOrderTemplateRepository
            ->expects(self::once())
            ->method('add')
            ->with($regulationOrderTemplate)
            ->willReturn($regulationOrderTemplate);

        $this->idFactory
            ->expects(self::once())
            ->method('make')
            ->willReturn('9cebe00d-04d8-48da-89b1-059f6b7bfe44');

        $this->dateUtils
            ->expects(self::once())
            ->method('getNow')
            ->willReturn(new \DateTimeImmutable('2025-04-07'));

        $handler = new SaveRegulationOrderTemplateCommandHandler(
            $this->idFactory,
            $this->regulationOrderTemplateRepository,
            $this->dateUtils,
        );
        $command = new SaveRegulationOrderTemplateCommand($organization);
        $command->name = 'Restriction de vitesse';
        $command->title = 'Arrete temporaire n°[numero_arrete]';
        $command->visaContent = 'VU ...';
        $command->consideringContent = 'CONSIDERANT ...';
        $command->articleContent = 'ARTICLES ...';

        $this->assertSame($regulationOrderTemplate, $handler($command));
    }

    public function testUpdate(): void
    {
        $organization = $this->createMock(Organization::class);
        $regulationOrderTemplate = $this->createMock(RegulationOrderTemplate::class);
        $regulationOrderTemplate
            ->expects(self::once())
            ->method('update')
            ->with(
                'Restriction de vitesse updated',
                'Arrete temporaire n°[numero_arrete] updated',
                'VU ... updated',
                'CONSIDERANT ... updated',
                'ARTICLES ... updated',
            );

        $this->regulationOrderTemplateRepository
            ->expects(self::never())
            ->method('add');

        $this->idFactory
            ->expects(self::never())
            ->method('make');

        $this->dateUtils
            ->expects(self::never())
            ->method('getNow');

        $handler = new SaveRegulationOrderTemplateCommandHandler(
            $this->idFactory,
            $this->regulationOrderTemplateRepository,
            $this->dateUtils,
        );

        $command = new SaveRegulationOrderTemplateCommand($organization, $regulationOrderTemplate);
        $command->name = 'Restriction de vitesse updated';
        $command->title = 'Arrete temporaire n°[numero_arrete] updated';
        $command->visaContent = 'VU ... updated';
        $command->consideringContent = 'CONSIDERANT ... updated';
        $command->articleContent = 'ARTICLES ... updated';

        $this->assertSame($regulationOrderTemplate, $handler($command));
    }
}
