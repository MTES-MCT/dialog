<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class ContactControllerTest extends AbstractWebTestCase
{
    public function testLanding(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('Nous contacter', $crawler->filter('h1')->text());
        $this->assertSame('Contactez-nous par email', $crawler->filter('h2')->text());
        $contactLink = $crawler->filter('[data-testid="contact-email"]');
        $this->assertSame('mailto:dialog@beta.gouv.fr', $contactLink->attr('href'));
        $this->assertMetaTitle('Nous contacter - DiaLog', $crawler);
    }
}
