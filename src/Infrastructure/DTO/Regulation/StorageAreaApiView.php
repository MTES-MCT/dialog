<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\Measure\StorageAreaView;

final readonly class StorageAreaApiView
{
    public function __construct(
        public ?string $description,
    ) {
    }

    public static function fromView(StorageAreaView $view): self
    {
        return new self(description: $view->description);
    }
}
