<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class EcodesignStatementControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/ecoconception');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('public, s-maxage=86400', $client->getResponse()->headers->get('Cache-Control'));
        $this->assertMetaTitle("Déclaration d'écoconception - DiaLog", $crawler);
        $this->assertSame("Déclaration d'écoconception", $crawler->filter('h1')->text());
        $this->assertStringContainsString('partiellement conforme', $crawler->text());
    }

    public function testFooterLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);

        $link = $crawler->filter('footer')->selectLink('Écoconception');
        $this->assertSame('http://localhost/ecoconception', $link->link()->getUri());
    }
}
