<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Public;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class PublicRegulationControllerTest extends AbstractWebTestCase
{
    public function testDraftRegulation(): void
    {
        $client = static::createClient();
        $client->request('GET', '/public/' . RegulationOrderRecordFixture::UUID_TYPICAL);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testRegulation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/public/' . RegulationOrderRecordFixture::UUID_PUBLISHED);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Arrêté temporaire FO2/2023', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Arrêté temporaire FO2/2023 - DiaLog', $crawler);

        $generalInfo = $crawler->filter('[data-testid="general_info"]')->eq(0);

        $this->assertSame(OrganizationFixture::MAIN_ORG_NAME, $generalInfo->filter('li')->eq(0)->text());
        $this->assertSame('Travaux', $generalInfo->filter('li')->eq(1)->text());
        $this->assertSame('Description 2', $generalInfo->filter('li')->eq(2)->text());
        $this->assertSame('Du 10/03/2023 au 20/03/2023', $generalInfo->filter('li')->eq(3)->text());

        $measureHeader = $crawler->filter('[data-testid="measure"]')->eq(0);
        $measureContent = $crawler->filter('[data-testid="measure-content"]')->eq(0);

        $this->assertSame('Circulation interdite', $measureHeader->filter('h3')->text());
        $this->assertSame('pour les véhicules de plus de 3,5 tonnes, 12 mètres de long ou 2,4 mètres de haut, matières dangereuses, Crit\'Air 4 et Crit\'Air 5, sauf piétons, véhicules d\'urgence et convois exceptionnels', $measureContent->filter('li')->eq(0)->text());
        $this->assertSame('tous les jours', $measureContent->filter('li')->eq(1)->text());
        $this->assertSame('Rue de l\'Hôtel de Ville du n° 30 au n° 12 Montauban (82000)', $measureContent->filter('li')->eq(3)->text());
        $this->assertSame('Rue Gamot Montauban (82000)', $measureContent->filter('.app-card__content li')->eq(4)->text());
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/public/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST);

        $this->assertResponseStatusCodeSame(404);
    }
}
