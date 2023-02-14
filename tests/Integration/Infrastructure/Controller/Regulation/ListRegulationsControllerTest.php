<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ListRegulationsControllerTest extends AbstractWebTestCase
{
    public function testList(): void
    {
        $client = $this->login();
        $pageOne = $client->request('GET', '/regulations?pageSize=1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Réglementations', $pageOne->filter('h3')->text());

        $tabs = $pageOne->filter('.fr-tabs__list')->eq(0);

        $this->assertSame("tablist", $tabs->attr("role"));
        $this->assertSame("Brouillons (2) Publiée (1)", $tabs->text());

        // Test draft reglementation rendering
        $pageOneDraftRows = $pageOne->filter('#draft-panel tbody > tr');
        $this->assertSame(1, $pageOneDraftRows->count()); // One item per page

        $pageOneDraftRow0 = $pageOneDraftRows->eq(0)->filter('td');
        $this->assertEmpty($pageOneDraftRow0->eq(0)->text()); // No location
        $this->assertEmpty($pageOneDraftRow0->eq(1)->text()); // No period set
        $this->assertSame("Brouillon", $pageOneDraftRow0->eq(2)->text());

        $links = $pageOneDraftRow0->eq(3)->filter('a');
        $this->assertSame("Modifier", $links->eq(0)->text());
        $this->assertSame("http://localhost/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa", $links->eq(0)->link()->getUri());

        $pageTwo = $client->request('GET', '/regulations?page=2&tab=draft&pageSize=1');
        $this->assertResponseStatusCodeSame(200);

        $tabs = $pageTwo->filter('.fr-tabs__list')->eq(0);
        $this->assertSame("Brouillons (2) Publiée (1)", $tabs->text());

        $pageTwoDraftRows = $pageTwo->filter('#draft-panel tbody > tr');
        $pageTwoDraftRow1 = $pageTwoDraftRows->eq(0)->filter('td');

        $this->assertSame("Savenay Route du Grand Brossais", $pageTwoDraftRow1->eq(0)->text());
        $this->assertSame("du 08/12/2022 au 18/12/2022", $pageTwoDraftRow1->eq(1)->text());
        $this->assertSame("Brouillon", $pageTwoDraftRow1->eq(2)->text());

        // Test published reglementation rendering
        $pageOnePublishedRows = $pageOne->filter('#published-panel tbody > tr');
        $this->assertSame(1, $pageOnePublishedRows->count());

        $pageOnePublishedRow0 = $pageOnePublishedRows->eq(0)->filter('td');
        $this->assertSame('Montauban Avenue de Fonneuve', $pageOnePublishedRow0->eq(0)->text());
        $this->assertSame("depuis le 08/10/2022 permanent", $pageOnePublishedRow0->eq(1)->text());
        $this->assertSame("Réglementation en cours", $pageOnePublishedRow0->eq(2)->text());
        $links = $pageOnePublishedRow0->eq(3)->filter('a');

        $this->assertSame("Voir le détail", $links->eq(0)->text());
        $this->assertSame("http://localhost/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5", $links->eq(0)->link()->getUri());

        // Test pagination rendering
        $navLi = $pageOne->filter('nav.fr-pagination')->filter('li');
        $this->assertSame("Première page", $navLi->eq(0)->filter('a')->text());
        $this->assertSame("Page précédente", $navLi->eq(1)->filter('a')->text());
        $this->assertSame("1", $navLi->eq(2)->filter('a')->text());
        $this->assertSame("2", $navLi->eq(3)->filter('a')->text());
        $this->assertSame("Page suivante", $navLi->eq(4)->filter('a')->text());
        $this->assertSame("Dernière page", $navLi->eq(5)->filter('a')->text());
    }

    public function testListWithOtherOrganization(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $pageOne = $client->request('GET', '/regulations');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $tabs = $pageOne->filter('.fr-tabs__list')->eq(0);

        $this->assertSame("tablist", $tabs->attr("role"));
        $this->assertSame("Brouillon (1) Publiée (0)", $tabs->text());

        // Test draft reglementation rendering
        $pageOneDraftRows = $pageOne->filter('#draft-panel tbody > tr');
        $this->assertSame(1, $pageOneDraftRows->count()); // One item per page

        $pageOneDraftRow0 = $pageOneDraftRows->eq(0)->filter('td');
        $this->assertEmpty($pageOneDraftRow0->eq(0)->text()); // No location
        $this->assertEmpty($pageOneDraftRow0->eq(1)->text()); // No period set
        $this->assertSame("Brouillon", $pageOneDraftRow0->eq(2)->text());

        $links = $pageOneDraftRow0->eq(3)->filter('a');
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
