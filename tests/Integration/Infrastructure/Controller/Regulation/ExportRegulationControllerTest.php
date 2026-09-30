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

        $documentXml = $this->extractDocumentXml($client->getResponse()->getContent());

        $this->assertStringContainsString('LE MAIRE DE Département de Seine-Saint-Denis,', $documentXml);

        // Les lignes vides saisies dans l'éditeur (<p><br></p>) doivent devenir
        // des paragraphes vides standards, et non des paragraphes stylés
        // contenant un saut de ligne (qui doublent la hauteur et héritent des
        // styles surdimensionnés du modèle).
        $this->assertStringContainsString('<w:p/>', $documentXml);
        $this->assertDoesNotMatchRegularExpression(
            '#<w:p>(?:<w:pPr>.*?</w:pPr>)?<w:r><w:br /></w:r></w:p>#s',
            $documentXml,
        );

        // Le saut de page avant l'annexe ne doit jamais être un <w:br w:type="page"/>,
        // qui peut produire une page entièrement blanche.
        $this->assertStringNotContainsString('<w:br w:type="page"', $documentXml);
    }

    private function extractDocumentXml(string $docxContent): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dialog_export_test_');

        try {
            file_put_contents($path, $docxContent);

            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path));

            $documentXml = $zip->getFromName('word/document.xml');
            $zip->close();

            $this->assertNotFalse($documentXml);

            return $documentXml;
        } finally {
            unlink($path);
        }
    }
}
