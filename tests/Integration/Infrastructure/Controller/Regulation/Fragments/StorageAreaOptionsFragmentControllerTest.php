<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class StorageAreaOptionsFragmentControllerTest extends AbstractWebTestCase
{
    public function testGetOptions(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/storage_area/options?roadNumber=N176&targetId=form_storageArea');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(
            'Sélectionner une aire de stockage Zone de stockage 18-22 N176 Voie de droite',
            trim($crawler->filter('[target=form_storageArea]')->text()),
        );
    }

    public function testGetOptionsNoResult(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/storage_area/options?roadNumber=N999&targetId=form_storageArea');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $this->assertSame(
            'Sélectionner une aire de stockage Aucune aire de stockage connue sur cette route',
            trim($crawler->filter('[target=form_storageArea]')->text()),
        );
    }

    public function testNotFoundQueryParamsMissing(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/storage_area/options');

        $this->assertResponseStatusCodeSame(404);
    }
}
