<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

final readonly class RegulationOrderTransformedView
{
    public function __construct(
        public string $title,
        public string $visaContent,
        public string $consideringContent,
        public string $articleContent,
        public ?string $logo = null,
        public ?string $logoMimeType = null,
    ) {
    }
}
