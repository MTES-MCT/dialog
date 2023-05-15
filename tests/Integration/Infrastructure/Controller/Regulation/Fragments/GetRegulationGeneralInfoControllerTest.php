<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetRegulationGeneralInfoControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/general_info');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame('Description 3', $crawler->filter('h3')->text());
        $this->assertSame('DiaLog', $crawler->filter('li')->eq(0)->text());
        $this->assertSame('Description 3', $crawler->filter('li')->eq(1)->text());
        $this->assertSame('Depuis le 11/03/2023', $crawler->filter('li')->eq(2)->text());
        $editForm = $crawler->selectButton('Modifier')->form();
        $this->assertSame('http://localhost/_fragment/regulations/general_info/form/4ce75a1f-82f3-40ee-8f95-48d0f04446aa', $editForm->getUri());
        $this->assertSame('GET', $editForm->getMethod());
    }

    public function testGetDescriptionTruncated(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/b1a3e982-39a1-4f0e-8a6f-ea2fd5e872c2/general_info');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Description 5 that is very long and...', $crawler->filter('h3')->text());
    }

    public function testGetPublished(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5/general_info');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(0, $crawler->filter('a')->count()); // Cannot edit
    }

    public function testRegulationDoesNotExist(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/3ede8b1a-1816-4788-8510-e08f45511aaa/general_info');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $client->request('GET', '/_fragment/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5/general_info');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/general_info');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
