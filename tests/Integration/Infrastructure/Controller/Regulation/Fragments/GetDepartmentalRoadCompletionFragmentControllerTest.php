<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetDepartmentalRoadCompletionFragmentControllerTest extends AbstractWebTestCase
{
    public function testDepartmentalRoadAutoComplete(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/road-number-completions?search=d32&administrator=Ardennes');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $li = $crawler->filter('li');

        $this->assertSame(4, $li->count());

        $this->assertSame('D32', $li->eq(0)->text());
        $this->assertSame('D322', $li->eq(1)->text());
        $this->assertSame('D322A', $li->eq(2)->text());
        $this->assertSame('D324', $li->eq(3)->text());
    }

    public function testBadRequest(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/road-number-completions');
        $client->getResponse();

        $this->assertResponseStatusCodeSame(400);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/road-number-completions?search=Test&administrator=Test');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
