<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class PrivacyControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/vie-privee');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('public, s-maxage=86400', $client->getResponse()->headers->get('Cache-Control'));
        $this->assertMetaTitle('Vie privée - DiaLog', $crawler);
        $this->assertSame('Vie privée', $crawler->filter('h1')->text());
    }

    public function testFooterLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);

        $link = $crawler->filter('footer')->selectLink('Vie privée');
        $this->assertSame('http://localhost/vie-privee', $link->link()->getUri());
    }
}
