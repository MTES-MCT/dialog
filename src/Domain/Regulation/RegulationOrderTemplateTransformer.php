<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Application\Regulation\View\GeneralInfoView;
use App\Application\StorageInterface;
use App\Domain\Organization\SigningAuthority\SigningAuthority;

final readonly class RegulationOrderTemplateTransformer
{
    public const VARIABLES = [
        '[numero_arrete]',
        '[intitule_arrete]',
        '[pouvoir_de_signature]',
        '[nom_commune]',
        '[nom_signataire]',
        '[departement]',
    ];

    public function __construct(
        private StorageInterface $storage,
    ) {
    }

    public function transform(
        RegulationOrderTemplate $regulationOrderTemplate,
        GeneralInfoView $generalInfo,
        ?SigningAuthority $signingAuthority = null,
    ): RegulationOrderTransformedView {
        // Les valeurs substituées sont des champs libres saisis par les utilisateurs :
        // elles sont échappées car le résultat est rendu en HTML brut (|raw) puis
        // transmis à Pandoc lors de l'export.
        $replacements = [
            self::VARIABLES[0] => $this->escape($generalInfo->identifier),
            self::VARIABLES[1] => $this->escape($generalInfo->title),
            self::VARIABLES[3] => $this->escape($generalInfo->organizationName),
            self::VARIABLES[5] => $this->escape($generalInfo->organizationAddress?->department),
        ];

        if ($signingAuthority) {
            $replacements[self::VARIABLES[4]] = $this->escape($signingAuthority->getSignatoryName());
            $replacements[self::VARIABLES[2]] = $this->escape($signingAuthority->getName());
        }

        $logo = null;
        $logoMimeType = null;

        if ($path = $generalInfo->organizationLogo) {
            $storage = $this->storage->read($path);
            $logo = $storage ? base64_encode($storage) : null;
            $logoMimeType = $this->storage->getMimeType($path);
        }

        return new RegulationOrderTransformedView(
            strtr($regulationOrderTemplate->getTitle(), $replacements),
            strtr($regulationOrderTemplate->getVisaContent(), $replacements),
            strtr($regulationOrderTemplate->getConsideringContent(), $replacements),
            strtr($regulationOrderTemplate->getArticleContent(), $replacements),
            $logo,
            $logoMimeType,
        );
    }

    private function escape(?string $value): ?string
    {
        return $value === null ? null : htmlspecialchars($value, \ENT_QUOTES, 'UTF-8');
    }
}
