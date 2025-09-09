<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class RegulationOrderTemplatesOptionsFragmentControllerTest extends AbstractWebTestCase
{
    public function testGetOptions(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulation_order_templates/options?organizationUuid=' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '&targetId=general_info_form_regulationOrderTemplateUuid');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(
            'Sélectionner un modèle d\'arrêté Réglementation de vitesse en agglomération Restriction de vitesse sur route nationale',
            trim($crawler->filter('[target=general_info_form_regulationOrderTemplateUuid]')->text()),
        );
    }

    public function testNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulation_order_templates/options');

        $this->assertResponseStatusCodeSame(404);
    }
}
