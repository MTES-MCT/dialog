<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Pandoc;

use PHPUnit\Framework\TestCase;

/**
 * Vérifie le comportement du filtre Lua appliqué par Pandoc lors de l'export
 * DOCX des arrêtés (data/pandoc/export-docx.lua), en inspectant l'AST natif
 * produit par Pandoc.
 */
final class ExportDocxLuaFilterTest extends TestCase
{
    public function testPageBreakDivBecomesPageBreakBeforeParagraph(): void
    {
        $output = $this->runPandoc('<p>Contenu</p><div style="page-break-before:always;"></div><p>Annexe</p>');

        // Un pageBreakBefore est ignoré par Word si le paragraphe est déjà en
        // haut de page, contrairement à <w:br w:type="page"/> qui peut créer
        // une page entièrement blanche.
        $this->assertStringContainsString('pageBreakBefore', $output);
        $this->assertStringNotContainsString('w:type=\"page\"', $output);
    }

    public function testBlankQuillParagraphBecomesEmptyDocxParagraph(): void
    {
        $output = $this->runPandoc('<p><br></p>');

        $this->assertStringContainsString('RawBlock (Format "openxml") "<w:p/>"', $output);
    }

    public function testBlankHeadingBecomesEmptyDocxParagraph(): void
    {
        $output = $this->runPandoc('<h2><br></h2>');

        $this->assertStringContainsString('RawBlock (Format "openxml") "<w:p/>"', $output);
        $this->assertStringNotContainsString('Header', $output);
    }

    public function testBlankParagraphInsideCustomStyleDivLosesTheStyle(): void
    {
        $output = $this->runPandoc('<div custom-style="Dialog_TitrePrincipal"><p><br></p><p>Titre</p></div>');

        $this->assertStringContainsString('RawBlock (Format "openxml") "<w:p/>"', $output);
        $this->assertStringContainsString('Dialog_TitrePrincipal', $output);
    }

    public function testLineBreaksInsideTextArePreserved(): void
    {
        $output = $this->runPandoc('<p>VU le code<br>VU la loi</p>');

        $this->assertStringContainsString('LineBreak', $output);
        $this->assertStringNotContainsString('RawBlock', $output);
    }

    private function runPandoc(string $html): string
    {
        return (new \Pandoc\Pandoc())
            ->from('html')
            ->input($html)
            ->option('lua-filter', \dirname(__DIR__, 4) . '/data/pandoc/export-docx.lua')
            ->to('native')
            ->run();
    }
}
