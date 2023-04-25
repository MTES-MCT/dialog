<?php

declare(strict_types=1);

namespace App\Tests\E2E;

class BrowserTest extends AbstractBrowserTest
{
    public function testApp(): void
    {
        $client = static::createPantherClient();
        $client->request('GET', '/');
        $this->assertPageTitleContains('DiaLog');
    }
}
