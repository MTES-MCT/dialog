<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class RegulationOrderTemplatesAndIdentifiersFragmentControllerTest extends AbstractWebTestCase
{
    public function testGetAndIdentifiers(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulation_order_templates_and_identifiers?organizationUuid=' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '&targetId=general_info_form_regulationOrderTemplateUuid&identifierTargetId=general_info_form_identifier&identifierTargetName=general_info_form[identifier]');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(
            'Sélectionner un modèle d\'arrêté Réglementation de vitesse en agglomération Restriction de vitesse sur route nationale',
            trim($crawler->filter('[target=general_info_form_regulationOrderTemplateUuid]')->text()),
        );

        $this->assertSame(
            '2023-06-0001',
            $crawler->filter('#general_info_form_identifier')->attr('value'),
        );
    }

    public function testNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulation_order_templates_and_identifiers');

        $this->assertResponseStatusCodeSame(404);
    }
}
