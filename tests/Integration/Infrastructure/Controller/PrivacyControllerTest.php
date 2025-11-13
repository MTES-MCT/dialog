<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class PrivacyControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/politique-de-confidentialite');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('public, s-maxage=86400', $client->getResponse()->headers->get('Cache-Control'));
        $this->assertMetaTitle('Politique de confidentialité - DiaLog', $crawler);
        $this->assertSame('Politique de confidentialité', $crawler->filter('h1')->text());
    }

    public function testFooterLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);

        $link = $crawler->filter('footer')->selectLink('Politique de confidentialité');
        $this->assertSame('http://localhost/politique-de-confidentialite', $link->link()->getUri());
    }
}
