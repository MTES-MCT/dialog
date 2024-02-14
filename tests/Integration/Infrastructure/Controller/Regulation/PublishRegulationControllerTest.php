<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class PublishRegulationControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    public function testPublish(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/publish', [
            'token' => $this->generateCsrfToken($client, 'publish-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(303);
        $client->followRedirect();
        $this->assertRouteSame('app_regulation_detail', ['uuid' => RegulationOrderRecordFixture::UUID_TYPICAL]);
    }

    public function testCannotBePublishedBecauseNoMeasures(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_NO_LOCATIONS . '/publish', [
            'token' => $this->generateCsrfToken($client, 'publish-regulation'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST . '/publish', [
            'token' => $this->generateCsrfToken($client, 'publish-regulation'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCannotPublishBecauseDifferentOrganization(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/publish', [
            'token' => $this->generateCsrfToken($client, 'publish-regulation'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testInvalidCsrfToken(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/publish');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/publish');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
