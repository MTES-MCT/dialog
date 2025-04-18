<?php

declare(strict_types=1);

namespace App\Application\Regulation\View\Measure;

final readonly class StorageAreaView
{
    public function __construct(
        public ?string $description,
    ) {
    }
}
