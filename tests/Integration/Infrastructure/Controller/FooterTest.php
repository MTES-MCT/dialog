<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

final class FooterTest extends AbstractWebTestCase
{
    public function testFooterExtraLinks(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
        $link = $crawler->filter('footer')->selectLink('Code source');
        $this->assertSame('https://github.com/MTES-MCT/dialog', $link->link()->getUri());
    }
}
