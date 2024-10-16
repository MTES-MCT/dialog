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
        $crawler = $client->request('GET', '/_fragment/visa_models/options?organizationUuid=' . OrganizationFixture::MAIN_ORG_ID);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(trim($crawler->text()), 'Sélectionner un modèle de visas Réglementation de vitesse en agglomération Interdiction de circulation');
    }

    public function testNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/visa_models/options');

        $this->assertResponseStatusCodeSame(404);
    }
}
