<?php

declare(strict_types=1);

namespace App\Application\Geography\View;

final readonly class AdministrativeBoundaryView
{
    public function __construct(
        public string $codeType,
        public string $code,
        public string $name,
    ) {
    }
}
