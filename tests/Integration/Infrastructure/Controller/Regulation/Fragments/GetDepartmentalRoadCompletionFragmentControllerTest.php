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

        $this->assertSame(1, $li->count());

        $this->assertSame('D322', $li->eq(0)->text());
        $this->assertSame('{"type":"MultiLineString","coordinates":[[[4.66349228,49.8207711],[4.66356107,49.82070816],[4.6636232,49.8206543],[4.66372513,49.82058551],[4.66385317,49.82050828],[4.66399657,49.82043354],[4.66415639,49.82035139],[4.6643028,49.82028379],[4.66443686,49.82022086],[4.66459579,49.82015399],[4.6647601,49.82008166]]]}', $li->eq(0)->attr('data-autocomplete-value'));
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
