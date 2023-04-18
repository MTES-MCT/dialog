<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ListRegulationsControllerTest extends AbstractWebTestCase
{
    public function testPageAndTabs(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Arrêtés de circulation', $crawler->filter('h3')->text());
        $this->assertMetaTitle("Arrêtés de circulation - DiaLog", $crawler);

        $tabs = $crawler->filter('.fr-tabs__list')->eq(0);
        $this->assertSame("tablist", $tabs->attr("role"));
        $this->assertSame("Temporaires (3) Permanents (1)", $tabs->text());
    }

    public function testTemporaryRegulationRendering(): void
    {
        $client = $this->login();

        // First item
        $pageOne = $client->request('GET', '/regulations?pageSize=1');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $pageOneTemporaryRows = $pageOne->filter('#temporary-panel tbody > tr');
        $this->assertSame(1, $pageOneTemporaryRows->count()); // One item per page

        $pageOneTemporaryRow0 = $pageOneTemporaryRows->eq(0)->filter('td');
        $this->assertSame("FO1/2023", $pageOneTemporaryRow0->eq(0)->text());
        $this->assertSame('Route du Grand Brossais, 44260 Savenay', $pageOneTemporaryRow0->eq(1)->text());
        $this->assertSame("du 13/03/2023 au 15/03/2023 passé", $pageOneTemporaryRow0->eq(2)->text());
        $this->assertSame("Brouillon", $pageOneTemporaryRow0->eq(3)->text());

        $links = $pageOneTemporaryRow0->eq(4)->filter('a');
        $this->assertSame("Modifier", $links->eq(0)->text());
        $this->assertSame("http://localhost/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5", $links->eq(0)->link()->getUri());

        // Second item
        $pageTwo = $client->request('GET', '/regulations?pageSize=1&tab=temporary&page=2');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $pageTwoTemporaryRows = $pageTwo->filter('#temporary-panel tbody > tr');
        $this->assertSame(1, $pageTwoTemporaryRows->count());

        $pageTwoTemporaryRow0 = $pageTwoTemporaryRows->eq(0)->filter('td');
        $this->assertSame('FO1/2023 (copie)', $pageTwoTemporaryRow0->eq(0)->text());
        $this->assertEmpty($pageTwoTemporaryRow0->eq(1)->text()); // No location set
        $this->assertSame("du 13/03/2023 au 15/03/2023 passé", $pageTwoTemporaryRow0->eq(2)->text());
        $this->assertSame('Brouillon', $pageTwoTemporaryRow0->eq(3)->text());

        $links = $pageOneTemporaryRow0->eq(4)->filter('a');
        $this->assertSame("Modifier", $links->eq(0)->text());
        $this->assertSame("http://localhost/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5", $links->eq(0)->link()->getUri());
    }

    public function testPublishedRegulationRendering(): void
    {
        $client = $this->login();
        $pageThree = $client->request('GET', '/regulations?pageSize=1&tab=temporary&page=3');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $pageThreeTemporaryRows = $pageThree->filter('#temporary-panel tbody > tr');
        $this->assertSame(1, $pageThreeTemporaryRows->count());

        $pageThreeTemporaryRow0 = $pageThreeTemporaryRows->eq(0)->filter('td');
        $this->assertSame('FO2/2023', $pageThreeTemporaryRow0->eq(0)->text());
        $this->assertSame('Avenue de Fonneuve, 82000 Montauban', $pageThreeTemporaryRow0->eq(1)->text());
        $this->assertSame('du 10/03/2023 au 20/03/2023 passé', $pageThreeTemporaryRow0->eq(2)->text());
        $this->assertSame("Publié", $pageThreeTemporaryRow0->eq(3)->text());

        $links = $pageThreeTemporaryRow0->eq(4)->filter('a');
        $this->assertSame("Voir le détail", $links->eq(0)->text());
        $this->assertSame("http://localhost/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5", $links->eq(0)->link()->getUri());
    }

    public function testPermanentRegulationRendering(): void
    {
        $client = $this->login();
        $pageOne = $client->request('GET', '/regulations?pageSize=1&tab=permanent&page=1');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $pageOnePermanentRows = $pageOne->filter('#permanent-panel tbody > tr');
        $this->assertSame(1, $pageOnePermanentRows->count());

        $pageOnePermanentRow0 = $pageOnePermanentRows->eq(0)->filter('td');
        $this->assertSame("FO3/2023", $pageOnePermanentRow0->eq(0)->text());
        $this->assertSame('75018 Paris 18e Arrondissement', $pageOnePermanentRow0->eq(1)->text());
        $this->assertSame('à partir du 11/03/2023 en cours', $pageOnePermanentRow0->eq(2)->text());
        $this->assertSame('Brouillon', $pageOnePermanentRow0->eq(3)->text());

        $links = $pageOnePermanentRow0->eq(4)->filter('a');
        $this->assertSame('Modifier', $links->eq(0)->text());
        $this->assertSame("http://localhost/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa", $links->eq(0)->link()->getUri());
    }

    public function testPaginationRendering(): void
    {
        $client = $this->login();
        $pageOne = $client->request('GET', '/regulations?pageSize=1&tab=permanent&page=1');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $navLi = $pageOne->filter('nav.fr-pagination')->filter('li');
        $this->assertSame("Première page", $navLi->eq(0)->filter('a')->text());
        $this->assertSame("Page précédente", $navLi->eq(1)->filter('a')->text());
        $this->assertSame("1", $navLi->eq(2)->filter('a')->text());
        $this->assertSame("2", $navLi->eq(3)->filter('a')->text());
        $this->assertSame("3", $navLi->eq(4)->filter('a')->text());
        $this->assertSame("Page suivante", $navLi->eq(5)->filter('a')->text());
        $this->assertSame("Dernière page", $navLi->eq(6)->filter('a')->text());
    }

    public function testListWithOtherOrganization(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $pageOne = $client->request('GET', '/regulations');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $tabs = $pageOne->filter('.fr-tabs__list')->eq(0);
        $this->assertSame("tablist", $tabs->attr("role"));
        $this->assertSame("Temporaires (0) Permanents (1)", $tabs->text());

        $pageOnePermanentRows = $pageOne->filter('#permanent-panel tbody > tr');
        $this->assertSame(1, $pageOnePermanentRows->count()); // One item per page

        $pageOnePermanentRow0 = $pageOnePermanentRows->eq(0)->filter('td');
        $this->assertSame('FO4/2023', $pageOnePermanentRow0->eq(0)->text());
        $this->assertEmpty($pageOnePermanentRow0->eq(1)->text()); // No location set
        $this->assertEmpty($pageOnePermanentRow0->eq(2)->text()); // No period set
        $this->assertSame("Brouillon", $pageOnePermanentRow0->eq(3)->text());

        $links = $pageOnePermanentRow0->eq(4)->filter('a');
        $this->assertSame("Modifier", $links->eq(0)->text());
        $this->assertSame("http://localhost/regulations/867d2be6-0d80-41b5-b1ff-8452b30a95f5", $links->eq(0)->link()->getUri());
    }

    public function testInvalidPageSize(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations?pageSize=0');
        $this->assertResponseStatusCodeSame(400);

        $client->request('GET', '/regulations?pageSize=-1');
        $this->assertResponseStatusCodeSame(400);

        $client->request('GET', '/regulations?pageSize=abc');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testInvalidPageNumber(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations?page=0');
        $this->assertResponseStatusCodeSame(400);

        $client->request('GET', '/regulations?page=-1');
        $this->assertResponseStatusCodeSame(400);

        $client->request('GET', '/regulations?page=abc');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations');

        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
