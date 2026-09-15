<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\HtmlSanitizerInterface;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface as SymfonyHtmlSanitizerInterface;

final class RichTextHtmlSanitizer implements HtmlSanitizerInterface
{
    public function __construct(
        private readonly SymfonyHtmlSanitizerInterface $appRichTextSanitizer,
    ) {
    }

    public function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        return $this->appRichTextSanitizer->sanitize($html);
    }
}
