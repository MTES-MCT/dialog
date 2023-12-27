<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AddLocationLinkFragmentControllerTest extends AbstractWebTestCase
{
    public function testLink(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/add-link');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Ajouter une localisation');
        $form = $saveButton->form();
        $this->assertSame('http://localhost/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/add', $form->getUri());
    }

    public function testBadUuid(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/aaaaaaaa/location/add-link');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        // TODO user standard regulation order record
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/867d2be6-0d80-41b5-b1ff-8452b30a95f5/location/add-link');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
