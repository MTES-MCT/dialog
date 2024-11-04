<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class VisaModelsOptionsFragmentControllerTest extends AbstractWebTestCase
{
    public function testGetOptions(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/visa_models/options?organizationUuid=' . OrganizationFixture::MAIN_ORG_ID . '&targetId=general_info_form_visaModelUuid');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(
            'Sélectionner un modèle de visas Réglementation de vitesse en agglomération Interdiction de circulation',
            trim($crawler->filter('[target=general_info_form_visaModelUuid]')->text()),
        );
        $this->assertStringStartsWith('Les modèles de visas sont gérés', trim($crawler->filter('[target=visa-info]')->text()));
    }

    public function testNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/visa_models/options');

        $this->assertResponseStatusCodeSame(404);
    }
}
