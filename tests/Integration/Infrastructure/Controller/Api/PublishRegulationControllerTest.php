<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class PublishRegulationControllerTest extends AbstractWebTestCase
{
    public function testPublishRegulation(): void
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            \sprintf('/api/regulations/%s/publish', RegulationOrderRecordFixture::UUID_TYPICAL),
            [],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertSecurityHeaders();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(RegulationOrderRecordFixture::UUID_TYPICAL, $data['uuid']);
        $this->assertSame(RegulationOrderRecordStatusEnum::PUBLISHED->value, $data['status']);
    }

    public function testPublishForbiddenForAnotherOrganization(): void
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            \sprintf('/api/regulations/%s/publish', RegulationOrderRecordFixture::UUID_PARKING_PROHIBITED),
            [],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(403);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(403, $data['status']);
        $this->assertSame('Forbidden', $data['detail']);
    }

    public function testPublishReturns404WhenRegulationDoesNotExist(): void
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            \sprintf('/api/regulations/%s/publish', RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST),
            [],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(404, $data['status']);
        $this->assertSame('Not Found', $data['detail']);
    }

    public function testPublishFailsWhenRegulationWithoutMeasures(): void
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            \sprintf('/api/regulations/%s/publish', RegulationOrderRecordFixture::UUID_NO_MEASURES),
            [],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(400, $data['status']);
        $this->assertSame('L\'arrêté ne peut pas être publié.', $data['detail']);
    }

    public function testPublishUnauthorizedWithInvalidCredentials(): void
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            \sprintf('/api/regulations/%s/publish', RegulationOrderRecordFixture::UUID_TYPICAL),
            [],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'invalid',
                'HTTP_X_CLIENT_SECRET' => 'invalid',
            ],
        );

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['message' => 'Unauthorized'], $data);
    }
}
