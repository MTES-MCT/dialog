<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetAddressCompletionFragmentControllerTest extends AbstractWebTestCase
{
    public function testStreetAutoComplete(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/_fragment/address-completions?search=Rue Eugène Berthoud');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $li = $crawler->filter('li');
        $this->assertSame(2, $li->count());
        $this->assertSame('Rue Eugène Berthoud, 93400 Saint-Ouen-sur-Seine', $li->eq(0)->text());
        $this->assertSame('Impasse Eugène Berthou, 29480 Le Relecq-Kerhuon', $li->eq(1)->text());
    }

    public function testMunicipalityAutoComplete(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/_fragment/address-completions?search=Le Mesnil');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $li = $crawler->filter('li');
        $this->assertSame(3, $li->count());
        $this->assertSame('50580 Le Mesnil', $li->eq(0)->text());
        $this->assertSame('76240 Le Mesnil-Esnard', $li->eq(1)->text());
        $this->assertSame('78600 Le Mesnil-le-Roi', $li->eq(2)->text());
    }

    public function testBadRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/address-completions');
        $client->getResponse();

        $this->assertResponseStatusCodeSame(400);
    }
}
