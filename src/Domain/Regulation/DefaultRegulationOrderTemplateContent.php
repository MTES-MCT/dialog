<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

final class DefaultRegulationOrderTemplateContent
{
    public const DEFAULT_TITLE = '<p>Arrêté temporaire N°[numero_arrete]</p><p>[intitule_arrete]</p>';
    public const DEFAULT_VISA_CONTENT = '<p><b>VU</b> Texte à compléter</p><p><b>VU</b> Texte à compléter</p>';
    public const DEFAULT_CONSIDERING_CONTENT = '<span><b>Considérant</b> qu\'en raison de ...</span>';
    public const DEFAULT_ARTICLE_CONTENT = '<p><b>ARTICLE 1 -</b> [mesures]</p><p><b>ARTICLE 2 -</b> Texte à compléter</p>';
}
