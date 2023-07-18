<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class DownloadRegulationControllerTest extends AbstractWebTestCase
{
    public function testDownload(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/download');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertResponseHeaderSame('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertResponseHeaderSame('Content-Disposition', 'attachment; filename=e413a47e-5928-4353-a8b2-8b7dda27f9a5.docx');
    }
}
