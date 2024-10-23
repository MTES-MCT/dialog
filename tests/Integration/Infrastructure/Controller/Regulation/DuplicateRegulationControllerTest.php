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
            '_token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('Arrêté permanent FO3/2023-1', $crawler->filter('h2')->text());
        $this->assertSame('Copiée avec succès Vous pouvez modifier les informations que vous souhaitez dans cette copie de la réglementation.', $crawler->filter('div.fr-alert--success')->text());
        $measures = $crawler->filter('[data-testid="measure"]');
        // Measure
        $this->assertSame('Circulation interdite', $measures->eq(0)->filter('h3')->text());
        $this->assertSame('pour tous les véhicules', $measures->eq(0)->filter('.app-card__content li')->eq(0)->text());
        $this->assertSame('tous les jours', $measures->eq(0)->filter('.app-card__content li')->eq(1)->text());
        $this->assertSame('Rue du Simplon à Paris 18e Arrondissement (75018)', $measures->eq(0)->filter('.app-card__content li')->eq(3)->text());
    }

    public function testDuplicateMultiple(): void
    {
        $client = $this->login();

        // 1st duplicate of original
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);
        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('Arrêté permanent FO3/2023-1', $crawler->filter('h2')->text());
        $parts = explode('/', $crawler->getUri());
        $duplicate1Uuid = $parts[\count($parts) - 1];

        // 2nd duplicate of original
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);
        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('Arrêté permanent FO3/2023-2', $crawler->filter('h2')->text());

        // Duplicate of 1st duplicate
        $client->request('POST', '/regulations/' . $duplicate1Uuid . '/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);
        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('Arrêté permanent FO3/2023-1-1', $crawler->filter('h2')->text());

        // Delete 1st duplicate of original and recreate it
        $client->request('DELETE', '/regulations/' . $duplicate1Uuid, [
            '_token' => $this->generateCsrfToken($client, 'delete-regulation'),
        ]);
        $this->assertResponseStatusCodeSame(303);
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);
        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('Arrêté permanent FO3/2023-3', $crawler->filter('h2')->text());
    }

    public function testWithoutLocations(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_NO_LOCATIONS . '/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $crawler = $client->followRedirect();

        $this->assertSame('Arrêté temporaire F2023/no-locations-1', $crawler->filter('h2')->text());
        $this->assertSame('Copiée avec succès Vous pouvez modifier les informations que vous souhaitez dans cette copie de la réglementation.', $crawler->filter('div.fr-alert--success')->text());
    }

    public function testWithoutMeasures(): void
    {
        $client = $this->login();
        $crawler = $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_NO_MEASURES . '/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);
        $crawler = $client->followRedirect();

        $this->assertSame('Arrêté temporaire FO14/2023-1', $crawler->filter('h2')->text());
        $this->assertSame('Copiée avec succès Vous pouvez modifier les informations que vous souhaitez dans cette copie de la réglementation.', $crawler->filter('div.fr-alert--success')->text());
    }

    public function testCannotDuplicateBecauseDifferentOrg(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRegulationNotFound(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST . '/duplicate', [
            '_token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/duplicate');

        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
