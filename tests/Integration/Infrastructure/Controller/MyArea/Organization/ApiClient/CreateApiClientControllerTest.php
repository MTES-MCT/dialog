<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\ApiClient;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class CreateApiClientControllerTest extends AbstractWebTestCase
{
    public function testCreateApiClientForm(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/api-clients/create');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Créer une clé d\'API', $crawler->filter('h3')->first()->text());

        $submitButton = $crawler->selectButton('Créer la clé');
        $form = $submitButton->form();
        $values = $form->getPhpValues();
        $values['create_api_client_form']['user'] = '0b507871-8b5e-4575-b297-a630310fc06e'; // department93User uuid
        $values['create_api_client_form']['_token'] = $form['create_api_client_form[_token]']->getValue();

        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_config_api_clients_list');
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::REGION_IDF_ID . '/api-clients/create');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/api-clients/create');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
