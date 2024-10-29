<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class CGUControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/conditions-generales-d-utilisation');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('public, s-maxage=86400', $client->getResponse()->headers->get('Cache-Control'));
        $this->assertMetaTitle('Conditions générales d’utilisation - DiaLog', $crawler);
        $this->assertSame('Conditions générales d’utilisation', $crawler->filter('h1')->text());
    }

    public function testFooterLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);

        $link = $crawler->filter('footer')->selectLink('Conditions générales d’utilisation');
        $this->assertSame('http://localhost/conditions-generales-d-utilisation', $link->link()->getUri());
    }
}
