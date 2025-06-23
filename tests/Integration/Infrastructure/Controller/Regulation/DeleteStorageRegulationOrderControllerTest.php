<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DeleteStorageRegulationOrderControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    public function testDelete(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');

        $client->request('DELETE', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/storage/delete', [
            '_token' => $this->generateCsrfToken($client, 'delete-storage'),
        ]);
        $this->assertResponseRedirects('/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL, 303);
        $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulation_detail');
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/storage/delete', [
            '_token' => $this->generateCsrfToken($client, 'delete-storage'),
        ]);
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
