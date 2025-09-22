<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Application\Regulation\View\GeneralInfoView;
use App\Application\StorageInterface;
use App\Domain\Organization\SigningAuthority\SigningAuthority;
use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\Regulation\RegulationOrderTemplateTransformer;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class RegulationOrderTemplateTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects(self::once())
            ->method('read')
            ->with('/path/to/logo.png')
            ->willReturn('PNG_BYTES');

        $storage
            ->expects(self::once())
            ->method('getMimeType')
            ->with('/path/to/logo.png')
            ->willReturn('image/png');

        $transformer = new RegulationOrderTemplateTransformer($storage);

        $template = (new RegulationOrderTemplate('11111111-1111-1111-1111-111111111111'))
            ->setTitle('Arrêté n°[numero_arrete] - [nom_commune]')
            ->setVisaContent('VU [intitule_arrete] par [pouvoir_de_signature]')
            ->setConsideringContent('CONSIDERANT [nom_signataire]')
            ->setArticleContent('ARTICLE 1: ...');

        $generalInfo = new GeneralInfoView(
            uuid: '22222222-2222-2222-2222-222222222222',
            identifier: 'ABC/2025',
            organizationName: 'Ville de Test',
            organizationLogo: '/path/to/logo.png',
            organizationUuid: '33333333-3333-3333-3333-333333333333',
            organizationAddress: null,
            status: 'DRAFT',
            regulationOrderUuid: '44444444-4444-4444-4444-444444444444',
            regulationOrderTemplateUuid: '55555555-5555-5555-5555-555555555555',
            category: 'TEMPORARY_REGULATION',
            subject: null,
            otherCategoryText: null,
            title: 'Intitulé de l\'arrêté',
            startDate: null,
            endDate: null,
        );

        $organization = (new Organization('66666666-6666-6666-6666-666666666666'))
            ->setName('Ville de Test');

        $signingAuthority = new SigningAuthority(
            uuid: '77777777-7777-7777-7777-777777777777',
            name: 'Le Maire',
            role: 'Maire',
            signatoryName: 'Jean Dupont',
            organization: $organization,
        );

        $view = $transformer->transform($template, $generalInfo, $signingAuthority);

        $this->assertSame('Arrêté n°ABC/2025 - Ville de Test', $view->title);
        $this->assertSame('VU Intitulé de l\'arrêté par Le Maire', $view->visaContent);
        $this->assertSame('CONSIDERANT Jean Dupont', $view->consideringContent);
        $this->assertSame('ARTICLE 1: ...', $view->articleContent);
        $this->assertSame(base64_encode('PNG_BYTES'), $view->logo);
        $this->assertSame('image/png', $view->logoMimeType);
    }
}
