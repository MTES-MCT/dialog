<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ListRegulationsControllerTest extends WebTestCase
{
    public function testList(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Réglementations', $crawler->filter('h3')->text());

        $tabs = $crawler->filter('.fr-tabs__list')->eq(0);

        $this->assertSame("tablist", $tabs->attr("role"));
        $this->assertSame("Brouillons (2) Publiée (1)", $tabs->text());

        $draftRows = $crawler->filter('#draft-panel tbody > tr');
        $this->assertSame(2, $draftRows->count());

        $draftRow0 = $draftRows->eq(0)->filter('td');
        $this->assertEmpty($draftRow0->eq(0)->text()); // No location
        $this->assertEmpty($draftRow0->eq(1)->text()); // No period set
        $this->assertSame("Brouillon", $draftRow0->eq(2)->text());

        $draftRow1 = $draftRows->eq(1)->filter('td');
        $this->assertSame("Savenay Route du Grand Brossais", $draftRow1->eq(0)->text());
        $this->assertSame("du 08/12/2022 au 18/12/2022", $draftRow1->eq(1)->text());
        $this->assertSame("Brouillon", $draftRow1->eq(2)->text());

        $publishedRows = $crawler->filter('#published-panel tbody > tr');
        $this->assertSame(1, $publishedRows->count());

        $publishedRow0 = $publishedRows->eq(0)->filter('td');
        $this->assertEmpty($publishedRow0->eq(0)->text()); // No location
        $this->assertSame("depuis le 08/10/2022 permanent", $publishedRow0->eq(1)->text());
        $this->assertSame("Réglementation en cours", $publishedRow0->eq(2)->text());
    }
}
