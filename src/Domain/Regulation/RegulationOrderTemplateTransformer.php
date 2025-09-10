<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

final class RegulationOrderTemplateTransformer
{
    public const VARIABLES = [
        '[numero_arrete]',
        '[intitule_arrete]',
        '[pouvoir_de_signature]',
        '[nom_commune]',
        '[nom_signataire]',
        '[mesures]',
    ];

    public function transform(RegulationOrderTemplate $regulationOrderTemplate): string
    {
        $title = $regulationOrderTemplate->getTitle();
        $visaContent = $regulationOrderTemplate->getVisaContent();
        $consideringContent = $regulationOrderTemplate->getConsideringContent();
        $articleContent = $regulationOrderTemplate->getArticleContent();

        foreach (self::VARIABLES as $variable) {
            $title = str_replace($variable, $variable, $title);
            $visaContent = str_replace($variable, $variable, $visaContent);
            $consideringContent = str_replace($variable, $variable, $consideringContent);
            $articleContent = str_replace($variable, $variable, $articleContent);
        }
        return $title;
    }
}
