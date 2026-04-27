<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AddStorageRegulationOrderControllerTest extends AbstractWebTestCase
{
    public function testAddStorageWithFile(): void
    {
        $client = static::createClient();

        $uploadedFile = new UploadedFile(
            __DIR__ . '/../../../../fixtures/file_too_large.pdf',
            'arrete.pdf',
            'application/pdf',
            null,
            true,
        );

        // FO2/2023 (publishedRegulationOrder) appartient à seineSaintDenisOrg et n'a pas encore de storage
        $client->request(
            'POST',
            '/api/regulations/FO2/2023/storage',
            ['title' => 'Arrêté'],
            ['file' => $uploadedFile],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('FO2/2023', $data['identifier']);
        $this->assertNull($data['url']);
        $this->assertSame('Arrêté', $data['title']);
        $this->assertSame('PDF', $data['mimeType']);
        $this->assertGreaterThan(0, $data['fileSize']);
    }

    public function testAddStorageWithUrl(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/regulations/FO2/2023/storage',
            [
                'url' => 'https://example.com/arrete.pdf',
                'title' => 'Arrêté municipal',
            ],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('FO2/2023', $data['identifier']);
        $this->assertSame('https://example.com/arrete.pdf', $data['url']);
        $this->assertSame('Arrêté municipal', $data['title']);
        $this->assertNull($data['mimeType']);
        $this->assertNull($data['fileSize']);
    }

    public function testUpdateExistingStorageReturns200(): void
    {
        $client = static::createClient();

        // FO1/2023 (typicalRegulationOrder) a déjà un storage attaché via StorageRegulationOrderFixture
        $client->request(
            'POST',
            \sprintf('/api/regulations/%s/storage', RegulationOrderFixture::TYPICAL_IDENTIFIER),
            [
                'url' => 'https://example.com/arrete-mis-a-jour.pdf',
                'title' => 'Mis à jour',
            ],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(RegulationOrderFixture::TYPICAL_IDENTIFIER, $data['identifier']);
        $this->assertSame('https://example.com/arrete-mis-a-jour.pdf', $data['url']);
        $this->assertSame('Mis à jour', $data['title']);
    }

    public function testReturns422WhenNeitherFileNorUrlProvided(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/regulations/FO2/2023/storage',
            ['title' => 'Test'],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(422, $data['status']);
        $this->assertSame('Validation failed', $data['detail']);
        $this->assertSame('Veuillez fournir soit un fichier, soit une URL.', $data['violations'][0]['title']);
    }

    public function testReturns422WhenBothFileAndUrlProvided(): void
    {
        $client = static::createClient();

        $uploadedFile = new UploadedFile(
            __DIR__ . '/../../../../fixtures/file_too_large.pdf',
            'arrete.pdf',
            'application/pdf',
            null,
            true,
        );

        $client->request(
            'POST',
            '/api/regulations/FO2/2023/storage',
            [
                'url' => 'https://example.com/arrete.pdf',
                'title' => 'Test',
            ],
            ['file' => $uploadedFile],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Veuillez fournir soit un fichier, soit une URL, mais pas les deux.', $data['violations'][0]['title']);
    }

    public function testReturns422WhenUrlInvalid(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/regulations/FO2/2023/storage',
            [
                'url' => 'not-a-url',
                'title' => 'Test',
            ],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $paths = array_map(static fn (array $v) => $v['propertyPath'], $data['violations']);
        $this->assertContains('url', $paths);
    }

    public function testReturns422WhenUrlAndTitleMissing(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/regulations/FO2/2023/storage',
            [
                'url' => 'https://example.com/arrete.pdf',
            ],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $paths = array_map(static fn (array $v) => $v['propertyPath'], $data['violations']);
        $this->assertContains('title', $paths);
    }

    public function testReturns422WhenFileExtensionInvalid(): void
    {
        $client = static::createClient();

        $uploadedFile = new UploadedFile(
            __DIR__ . '/../../../../fixtures/aires_de_stockage_test.csv',
            'aires_de_stockage_test.csv',
            'text/csv',
            null,
            true,
        );

        $client->request(
            'POST',
            '/api/regulations/FO2/2023/storage',
            [],
            ['file' => $uploadedFile],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $paths = array_map(static fn (array $v) => $v['propertyPath'], $data['violations']);
        $this->assertContains('file', $paths);
    }

    public function testReturns404WhenRegulationDoesNotExist(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/regulations/DOES-NOT-EXIST/storage',
            ['url' => 'https://example.com/arrete.pdf', 'title' => 'Test'],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(404, $data['status']);
        $this->assertSame('Not Found', $data['detail']);
    }

    public function testReturns404ForAnotherOrganization(): void
    {
        $client = static::createClient();

        // L'arrêté '2025-01' appartient à dialogOrg, pas à seineSaintDenisOrg
        $client->request(
            'POST',
            '/api/regulations/2025-01/storage',
            ['url' => 'https://example.com/arrete.pdf', 'title' => 'Test'],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUnauthorizedWithInvalidCredentials(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/regulations/FO2/2023/storage',
            ['url' => 'https://example.com/arrete.pdf', 'title' => 'Test'],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'invalid',
                'HTTP_X_CLIENT_SECRET' => 'invalid',
            ],
        );

        $this->assertResponseStatusCodeSame(401);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['message' => 'Unauthorized'], $data);
    }
}
