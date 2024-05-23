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

        $this->assertSame('1 résultat trouvé', $crawler->filter('template[id="status"]')->text());
        $options = $crawler->filter('li[role="option"]');
        $this->assertSame(1, $options->count());
        $this->assertSame('Rue Eugène Berthoud', $options->eq(0)->text());
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
