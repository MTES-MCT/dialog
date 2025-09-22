<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ExportRegulationControllerTest extends AbstractWebTestCase
{
    public function testDownloadWithoutTemplate(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/export.docx');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testDownloadWithTemplate(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_PUBLISHED . '/export.docx');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
    }
}
