<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DuplicateRegulationControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    public function testDuplicate(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();

        $this->assertSame('Arrêté permanent FO3/2023 (copie)', $crawler->filter('h2')->text());
        $this->assertSame('Copiée avec succès Vous pouvez modifier les informations que vous souhaitez dans cette copie de la réglementation.', $crawler->filter('div.fr-alert--success')->text());
        $location = $crawler->filter('[data-testid="location"]');

        // Location
        $this->assertSame('Rue du Simplon', $location->filter('h3')->text());
        $this->assertSame('Paris 18e Arrondissement (75018)', $location->filter('li')->eq(0)->text());
        $this->assertSame('Rue du Simplon', $location->filter('li')->eq(1)->text());
        $this->assertSame('Circulation alternée tous les jours pour tous les véhicules', $location->filter('li')->eq(2)->text());
        $this->assertSame('Circulation interdite du 09/06/2023 à 10h00 au 09/06/2023 à 10h00, le lundi pour les véhicules de plus de 3,5 tonnes', $location->filter('li')->eq(3)->text());
    }

    public function testWithoutLocations(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_NO_LOCATIONS . '/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $crawler = $client->followRedirect();

        $this->assertSame('Arrêté temporaire F2023/no-locations (copie)', $crawler->filter('h2')->text());
        $this->assertSame('Copiée avec succès Vous pouvez modifier les informations que vous souhaitez dans cette copie de la réglementation.', $crawler->filter('div.fr-alert--success')->text());
    }

    public function testWithoutMeasures(): void
    {
        $client = $this->login();
        $crawler = $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_NO_MEASURES . '/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);
        $crawler = $client->followRedirect();

        $this->assertSame('Arrêté temporaire FO14/2023 (copie)', $crawler->filter('h2')->text());
        $this->assertSame('Copiée avec succès Vous pouvez modifier les informations que vous souhaitez dans cette copie de la réglementation.', $crawler->filter('div.fr-alert--success')->text());
    }

    public function testDuplicateAnAlreadyExistingIdentifier(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_DUPLICATE_NAME_CONFLICT . '/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('L\'identifiant de l\'arrêté est déjà utilisé', $crawler->filter('div.fr-alert--error')->text());
    }

    public function testDuplicateWithNoStartDateYet(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_OTHER_ORG_NO_START_DATE . '/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(303);
    }

    public function testCannotDuplicateBcauseDifferentOrg(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRegulationNotFound(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST . '/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testInvalidCsrfToken(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/duplicate');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/duplicate');

        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
