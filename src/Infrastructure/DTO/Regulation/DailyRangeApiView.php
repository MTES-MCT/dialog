<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\DailyRangeView;

final readonly class DailyRangeApiView
{
    public function __construct(
        public ?array $dayRanges,
    ) {
    }

    public static function fromView(DailyRangeView $view): self
    {
        return new self(dayRanges: $view->dayRanges);
    }
}
