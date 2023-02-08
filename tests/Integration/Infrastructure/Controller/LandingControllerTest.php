<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;


final class LandingControllerTest extends AbstractWebTestCase
{
    public function testList(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Faciliter et sécuriser la circulation', $crawler->filter('h1')->text());
        $this->assertSame('Se connecter', $crawler->filter('div.user ul')->filter('li')->eq(0)->text()); // Logout user
    }
}
