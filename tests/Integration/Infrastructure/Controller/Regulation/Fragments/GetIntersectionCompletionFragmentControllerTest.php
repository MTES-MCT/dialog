<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetIntersectionCompletionFragmentControllerTest extends AbstractWebTestCase
{
    public function testIntersectionsAutoComplete(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/intersection-completions?roadName=Rue Agrippa d\'Aubigné&cityCode=75104');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('2 résultats', $crawler->filter('template[id="status"]')->text());
        $options = $crawler->filter('li[role="option"]');
        $this->assertSame(2, $options->count());
        $this->assertSame('Boulevard Morland', $options->eq(0)->text());
        $this->assertSame('Quai Henri Iv', $options->eq(1)->text());
    }

    public function testIntersectionsAutoCompleteWithSearch(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/intersection-completions?search=morl&roadName=Rue Agrippa d\'Aubigné&cityCode=75104');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('1 résultat', $crawler->filter('template[id="status"]')->text());
        $options = $crawler->filter('li[role="option"]');
        $this->assertSame(1, $options->count());
        $this->assertSame('Boulevard Morland', $options->eq(0)->text());
    }

    private function provideTestBadRequest(): array
    {
        return [
            'missing-roadName-cityCode' => ['/_fragment/intersection-completions'],
            'missing-roadName' => ['/_fragment/intersection-completions?cityCode=59606'],
            'missing-cityCode' => ['/_fragment/intersection-completions?roadName=Rue du Test'],
        ];
    }

    /**
     * @dataProvider provideTestBadRequest
     */
    public function testBadRequest(string $url): void
    {
        $client = $this->login();
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/intersection-completions?roadName=Rue du Test&cityCode=59606');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
