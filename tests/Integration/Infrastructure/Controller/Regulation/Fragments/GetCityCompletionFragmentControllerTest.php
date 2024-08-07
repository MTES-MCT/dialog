<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetCityCompletionFragmentControllerTest extends AbstractWebTestCase
{
    public function testCityAutoComplete(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/city-completions?search=Mesnil');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('3 résultats', $crawler->filter('template[id="status"]')->text());
        $options = $crawler->filter('li[role="option"]');
        $this->assertSame(3, $options->count());

        $this->assertSame('Blanc Mesnil (93150)', $options->eq(0)->text());
        $this->assertSame('93007', $options->eq(0)->attr('data-autocomplete-value'));

        $this->assertSame('Le Mesnil-Esnard (76240)', $options->eq(1)->text());
        $this->assertSame('76429', $options->eq(1)->attr('data-autocomplete-value'));

        $this->assertSame('Le Mesnil-le-Roi (78600)', $options->eq(2)->text());
        $this->assertSame('78396', $options->eq(2)->attr('data-autocomplete-value'));
    }

    public function testCityAutoCompleteNoResults(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/city-completions?search=BlahBlah');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('Aucun résultat', $crawler->filter('template[id="status"]')->text());
        $options = $crawler->filter('li[role="option"]');
        $this->assertSame(0, $options->count());
    }

    public function testBadRequest(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/city-completions');
        $client->getResponse();

        $this->assertResponseStatusCodeSame(400);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/city-completions?search=Test');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
