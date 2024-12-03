<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetNumberedRoadCompletionFragmentControllerTest extends AbstractWebTestCase
{
    public function testGetDepartmentalRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/road-number-completions?search=rd32&roadType=departmentalRoad&administrator=Ardennes');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('4 résultats', $crawler->filter('template[id="status"]')->text());
        $options = $crawler->filter('li[role="option"]');
        $this->assertSame(4, $options->count());

        $this->assertSame('D32', $options->eq(0)->text());
        $this->assertSame('D322', $options->eq(1)->text());
        $this->assertSame('D322A', $options->eq(2)->text());
        $this->assertSame('D324', $options->eq(3)->text());
    }

    public function testGetNationalRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/road-number-completions?search=n17&roadType=nationalRoad&administrator=DIR Ouest');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('2 résultats', $crawler->filter('template[id="status"]')->text());
        $options = $crawler->filter('li[role="option"]');
        $this->assertSame(2, $options->count());

        $this->assertSame('N171', $options->eq(0)->text());
        $this->assertSame('N176', $options->eq(1)->text());
    }

    public function testBadRequestAdministratorMissing(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/road-number-completions?search=test&roadType=departmentalRoad');
        $client->getResponse();

        $this->assertResponseStatusCodeSame(400);
    }

    public function testBadRequestRoadTypeMissing(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/road-number-completions?search=test&administrator=Ardennes');
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
