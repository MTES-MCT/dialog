<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

/** @group only */
final class AccessibilityDeclarationControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/accessibility');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('public, s-maxage=86400', $client->getResponse()->headers->get('Cache-Control'));
        $this->assertSame('Déclaration d\'accessibilité', $crawler->filter('h1')->text());
        $this->assertStringContainsString('non conforme', $crawler->text());
    }

    public function testFooterLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);

        $link = $crawler->filter('footer')->selectLink('Accessibilité : non conforme');
        $this->assertSame('http://localhost/accessibility', $link->link()->getUri());
    }
}
