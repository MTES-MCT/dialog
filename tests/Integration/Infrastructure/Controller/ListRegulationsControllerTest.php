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
        $this->assertSame('Restrictions de circulation', $crawler->filter('h3')->text());

        $firstTr = $crawler->filter('tbody > tr')->eq(0);
        $secondTr = $crawler->filter('tbody > tr')->eq(1);

        $this->assertSame("Autorité 1", $firstTr->filter('td')->eq(0)->text());
        $this->assertSame("du 08/12/2022 au 18/12/2022", $firstTr->filter('td')->eq(1)->text());

        $this->assertSame("Autorité 2", $secondTr->filter('td')->eq(0)->text());
        $this->assertSame("depuis le 08/10/2022 Permanent", $secondTr->filter('td')->eq(1)->text());
    }
}
