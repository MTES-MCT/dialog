<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Map\Fragment;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class MapSearchControllerTest extends AbstractWebTestCase
{
    public function testGetNoSearch(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/_fragment/map/search');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('Aucun résultat', $crawler->filter('#status')->innerText());

        $items = $crawler->filter('li');
        $this->assertCount(0, $items);
    }

    public function testGetEmpty(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/_fragment/map/search?search=EmptyPlease');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('Aucun résultat', $crawler->filter('#status')->innerText());

        $items = $crawler->filter('li');
        $this->assertCount(0, $items);
    }

    public function testGet(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/_fragment/map/search?search=Par');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('2 résultats', $crawler->filter('#status')->innerText());

        $items = $crawler->filter('li');
        $this->assertCount(2, $items);

        $this->assertSame('Rue du Parc', $items->eq(0)->innerText());
        $this->assertSame('{"coordinates":["x1","y1"],"kind":"street"}', $items->eq(0)->attr('data-autocomplete-value'));

        $this->assertSame('Paris', $items->eq(1)->innerText());
        $this->assertSame('{"coordinates":["x2","y2"],"kind":"administratif"}', $items->eq(1)->attr('data-autocomplete-value'));
    }
}
