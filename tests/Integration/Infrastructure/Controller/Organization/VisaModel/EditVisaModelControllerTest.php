<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Organization\VisaModel;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class EditVisaModelControllerTest extends AbstractWebTestCase
{
    private function visaModels(): array
    {
        return [
            'withDescription' => [
                'name' => 'Réglementation de vitesse',
                'description' => 'Limitation de vitesse à 30 Km/h dans toute la commune',
                'visas' => ['vu 1'],
            ],
            'withoutDescription' => [
                'name' => 'Réglementation de vitesse',
                'description' => null,
                'visas' => ['vu 2'],
            ],
        ];
    }

    /**
     * @dataProvider visaModels
     */
    public function testEdit(string $name, ?string $description, array $visas): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/visa_models/7eca6579-c07e-4e8e-8f10-fda610d7ee73/edit');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Réglementation de vitesse en agglomération', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Réglementation de vitesse en agglomération - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['visa_model_form']['name'] = $name;
        $values['visa_model_form']['description'] = $description;
        $values['visa_model_form']['visas'] = $visas;
        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_config_visa_models_list');
    }

    public function testBadFormValues(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/visa_models/7eca6579-c07e-4e8e-8f10-fda610d7ee73/edit');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['visa_model_form']['name'] = '';
        $values['visa_model_form']['visas'] = [''];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#visa_model_form_name_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#visa_model_form_visas_0_error')->text());
    }

    public function testVisaModelNotFound(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('GET', '/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/visa_models/99a1c489-6741-4db3-8618-119c43195a86/edit');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login();
        $client->request('GET', '/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/visa_models/7eca6579-c07e-4e8e-8f10-fda610d7ee73/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/organizations/' . OrganizationFixture::OTHER_ORG_ID . '/visa_models/7eca6579-c07e-4e8e-8f10-fda610d7ee73/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/visa_models/7eca6579-c07e-4e8e-8f10-fda610d7ee73/edit');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/visa_models/7eca6579-c07e-4e8e-8f10-fda610d7ee73/edit');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
