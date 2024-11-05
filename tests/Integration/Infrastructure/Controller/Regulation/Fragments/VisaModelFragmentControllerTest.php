<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class VisaModelFragmentControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/visa_models/detail?visaModelUuid=7eca6579-c07e-4e8e-8f10-fda610d7ee73');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(trim($crawler->text()), 'vu que 1 vu que 2');
    }

    public function testGetEmpty(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/visa_models/detail?visaModelUuid=');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(trim($crawler->text()), '');
    }

    public function testNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/visa_models/detail');

        $this->assertResponseStatusCodeSame(404);

        $client->request('GET', '/_fragment/visa_models/detail?visaModelUuid=ec8a5a2b-e054-45b0-8778-8b20cae4a5bc');

        $this->assertResponseStatusCodeSame(404);
    }
}
