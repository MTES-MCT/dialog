<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LandingControllerTest extends WebTestCase
{
    public function testList(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Faciliter et sécuriser la circulation', $crawler->filter('h3')->text());

        $this->assertSame('Se connecter', $crawler->filter('div.user ul')->filter('li')->eq(0)->text()); // Logout user
    }
}
