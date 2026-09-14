<?php

declare(strict_types=1);

namespace App\Application;

interface HtmlSanitizerInterface
{
    /**
     * Nettoie du HTML riche saisi par un utilisateur : seules les balises et
     * attributs de mise en forme autorisés sont conservés.
     */
    public function sanitize(?string $html): ?string;
}
