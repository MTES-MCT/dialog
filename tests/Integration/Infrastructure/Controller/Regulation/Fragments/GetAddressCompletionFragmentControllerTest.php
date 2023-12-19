<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetAddressCompletionFragmentControllerTest extends AbstractWebTestCase
{
    public function testStreetAutoComplete(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/address-completions?search=Rue Eugène Berthoud&cityCode=93070');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $li = $crawler->filter('li');
        $this->assertSame(1, $li->count());
        $this->assertSame('Rue Eugène Berthoud, 93400 Saint-Ouen-sur-Seine', $li->eq(0)->text());
    }

    public function testBadRequest(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/address-completions');
        $client->getResponse();

        $this->assertResponseStatusCodeSame(400);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/address-completions?search=Test');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
