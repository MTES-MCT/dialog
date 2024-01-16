<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DeleteRegulationControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    private function countRows($crawler)
    {
        $numTemporary = $crawler->filter('#temporary-panel tbody > tr:not([data-testid=empty-row])')->count();
        $numPermanent = $crawler->filter('#permanent-panel tbody > tr:not([data-testid=empty-row])')->count();

        return [$numTemporary, $numPermanent];
    }

    public function testDeleteTemporary(): void
    {
        $client = $this->login();

        $crawler = $client->request('GET', '/regulations');
        [$numTemporary, $numPermanent] = $this->countRows($crawler);

        $client->request('DELETE', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL, [
            'token' => $this->generateCsrfToken($client, 'delete-regulation'),
        ]);
        $this->assertResponseRedirects('/regulations?tab=temporary', 303);
        $crawler = $client->followRedirect();
        // Doesn't appear in list of temporary regulations anymore.
        $this->assertEquals([$numTemporary - 1, $numPermanent], $this->countRows($crawler));
    }

    public function testDeletePermanent(): void
    {
        $client = $this->login();

        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT);
        $this->assertResponseStatusCodeSame(200);

        $crawler = $client->request('GET', '/regulations');
        [$numTemporary, $numPermanent] = $this->countRows($crawler);

        $client->request('DELETE', '/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT, [
            'token' => $this->generateCsrfToken($client, 'delete-regulation'),
        ]);
        $this->assertResponseRedirects('/regulations?tab=permanent', 303);
        $crawler = $client->followRedirect();

        // Doesn't appear in list of permanent regulations anymore.
        $this->assertSame([$numTemporary, $numPermanent - 1], $this->countRows($crawler), $crawler->html());

        // Detail page doesn't exist anymore.
        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCannotDeleteBecauseDifferentOrganization(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('DELETE', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL, [
            'token' => $this->generateCsrfToken($client, 'delete-regulation'),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/regulations/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST, [
            'token' => $this->generateCsrfToken($client, 'delete-regulation'),
        ]);
        $this->assertResponseRedirects('/regulations?tab=temporary', 303);
    }

    public function testInvalidCsrfToken(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
