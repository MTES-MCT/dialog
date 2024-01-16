<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class ExportRegulationControllerTest extends AbstractWebTestCase
{
    public function testDownload(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/export.docx');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertResponseHeaderSame('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertResponseHasHeader('Content-Disposition', 'attachment; filename=' . RegulationOrderRecordFixture::UUID_TYPICAL . '.docx');
    }
}
