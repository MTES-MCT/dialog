<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\RegulationOrderTemplate;

use App\Application\DateUtilsInterface;
use App\Application\HtmlSanitizerInterface;
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
    private MockObject $htmlSanitizer;

    public function setUp(): void
    {
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->regulationOrderTemplateRepository = $this->createMock(RegulationOrderTemplateRepositoryInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->htmlSanitizer = $this->createMock(HtmlSanitizerInterface::class);
        // Par défaut, le sanitizer restitue le contenu tel quel
        $this->htmlSanitizer->method('sanitize')->willReturnArgument(0);
    }

    public function testAdd(): void
    {
        $organization = $this->createMock(Organization::class);

        $regulationOrderTemplate = new RegulationOrderTemplate('9cebe00d-04d8-48da-89b1-059f6b7bfe44');
        $regulationOrderTemplate
            ->setName('Restriction de vitesse')
            ->setTitle('Arrete temporaire n°[numero_arrete]')
            ->setVisaContent('VU ...')
            ->setConsideringContent('CONSIDERANT ...')
            ->setArticleContent('ARTICLES ...')
            ->setOrganization($organization)
            ->setCreatedAt(new \DateTimeImmutable('2025-04-07'));

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
            $this->htmlSanitizer,
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
            $this->htmlSanitizer,
        );

        $command = new SaveRegulationOrderTemplateCommand($organization, $regulationOrderTemplate);
        $command->name = 'Restriction de vitesse updated';
        $command->title = 'Arrete temporaire n°[numero_arrete] updated';
        $command->visaContent = 'VU ... updated';
        $command->consideringContent = 'CONSIDERANT ... updated';
        $command->articleContent = 'ARTICLES ... updated';

        $this->assertSame($regulationOrderTemplate, $handler($command));
    }

    public function testSanitizesHtmlContentBeforeSaving(): void
    {
        $organization = $this->createMock(Organization::class);
        $regulationOrderTemplate = $this->createMock(RegulationOrderTemplate::class);

        // Le sanitizer supprime le HTML malveillant du contenu de l'éditeur riche
        $htmlSanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $htmlSanitizer
            ->method('sanitize')
            ->willReturnCallback(static fn (?string $html): ?string => $html === null ? null : str_replace('<img src=x onerror="alert(1)">', '', $html));

        $regulationOrderTemplate
            ->expects(self::once())
            ->method('update')
            ->with(
                'Nom',
                'Titre',
                'VU ...',
                'CONSIDERANT ...',
                '<p>Article 1</p>',
            );

        $handler = new SaveRegulationOrderTemplateCommandHandler(
            $this->idFactory,
            $this->regulationOrderTemplateRepository,
            $this->dateUtils,
            $htmlSanitizer,
        );

        $command = new SaveRegulationOrderTemplateCommand($organization, $regulationOrderTemplate);
        $command->name = 'Nom';
        $command->title = 'Titre';
        $command->visaContent = 'VU ...';
        $command->consideringContent = 'CONSIDERANT ...';
        $command->articleContent = '<p>Article 1</p><img src=x onerror="alert(1)">';

        $this->assertSame($regulationOrderTemplate, $handler($command));
    }
}
