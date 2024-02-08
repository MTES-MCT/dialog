<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class LegalControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/mentions-legales');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('public, s-maxage=86400', $client->getResponse()->headers->get('Cache-Control'));
        $this->assertMetaTitle('Mentions légales - DiaLog', $crawler);
        $this->assertSame('Mentions légales', $crawler->filter('h1')->text());
    }

    public function testFooterLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);

        $link = $crawler->filter('footer')->selectLink('Mentions légales');
        $this->assertSame('http://localhost/mentions-legales', $link->link()->getUri());
    }
}
