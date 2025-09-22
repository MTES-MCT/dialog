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
        $replacements = [
            self::VARIABLES[0] => $generalInfo->identifier,
            self::VARIABLES[1] => $generalInfo->title,
            self::VARIABLES[3] => $generalInfo->organizationName,
        ];

        if ($signingAuthority) {
            $replacements[self::VARIABLES[4]] = $signingAuthority->getSignatoryName();
            $replacements[self::VARIABLES[2]] = $signingAuthority->getName();
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
}
