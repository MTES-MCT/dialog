<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation;

use App\Domain\Regulation\RegulationOrderTemplate;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class RegulationOrderTemplateTest extends TestCase
{
    public function testGetters(): void
    {
        $organization = $this->createMock(Organization::class);
        $createdAt = new \DateTimeImmutable('2025-04-07');

        $regulationOrderTemplate = new RegulationOrderTemplate(
            '6598fd41-85cb-42a6-9693-1bc45f4dd392',
            'Restriction de vitesse',
            'Arrete temporaire n°[numero_arrete]',
            'VU ...',
            'CONSIDERANT ...',
            'ARTICLES ...',
            $createdAt,
            $organization,
        );

        $this->assertSame('6598fd41-85cb-42a6-9693-1bc45f4dd392', $regulationOrderTemplate->getUuid());
        $this->assertSame('Restriction de vitesse', $regulationOrderTemplate->getName());
        $this->assertSame('VU ...', $regulationOrderTemplate->getVisaContent());
        $this->assertSame('CONSIDERANT ...', $regulationOrderTemplate->getConsideringContent());
        $this->assertSame('ARTICLES ...', $regulationOrderTemplate->getArticleContent());
        $this->assertSame('Arrete temporaire n°[numero_arrete]', $regulationOrderTemplate->getTitle());
        $this->assertSame($organization, $regulationOrderTemplate->getOrganization());
        $this->assertSame($createdAt, $regulationOrderTemplate->getCreatedAt());
    }
}
