<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\MapImage;

use App\Domain\Regulation\RegulationMapImageMakerInterface;

final readonly class WarmRegulationMapImageCacheCommandHandler
{
    public function __construct(
        private RegulationMapImageMakerInterface $regulationMapImageMaker,
    ) {
    }

    public function __invoke(WarmRegulationMapImageCacheCommand $command): void
    {
        // Renders + caches when the storage entry is missing, and is a no-op when already cached.
        $this->regulationMapImageMaker->make($command->regulationOrderRecordUuid);
    }
}
