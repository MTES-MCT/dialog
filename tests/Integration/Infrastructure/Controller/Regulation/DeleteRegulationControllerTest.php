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

    private function countRows($crawler): int
    {
        $num = $crawler->filter('.app-regulation-table tbody > tr:not([data-testid=empty-row])')->count();

        return $num;
    }

    public function testDelete(): void
    {
        $client = $this->login();

        $crawler = $client->request('GET', '/regulations');
        $num = $this->countRows($crawler);

        $client->request('DELETE', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL, [
            'token' => $this->generateCsrfToken($client, 'delete-regulation'),
        ]);
        $this->assertResponseRedirects('/regulations', 303);
        $crawler = $client->followRedirect();
        // Doesn't appear in list of temporary regulations anymore.
        $this->assertEquals($num - 1, $this->countRows($crawler));
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
        $this->assertResponseRedirects('/regulations', 303);
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
        $client->request('DELETE', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
