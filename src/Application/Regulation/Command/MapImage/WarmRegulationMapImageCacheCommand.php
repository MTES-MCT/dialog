<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\MapImage;

use App\Application\AsyncCommandInterface;

/**
 * Pre-warm the regulation map image cache so the docx export reads from storage instead of
 * paying the synchronous Playwright render cost.
 */
final class WarmRegulationMapImageCacheCommand implements AsyncCommandInterface
{
    public function __construct(
        public readonly string $regulationOrderRecordUuid,
    ) {
    }
}
