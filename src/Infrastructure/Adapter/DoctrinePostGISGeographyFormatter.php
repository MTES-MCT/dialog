<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\GeographyFormatterInterface;

final class DoctrinePostGISGeographyFormatter implements GeographyFormatterInterface
{
    // See: https://github.com/jsor/doctrine-postgis
    // "Values provided for the properties must be in the WKT format."

    public function formatPoint(float $latitude, float $longitude): string
    {
        // NOTE: We need to decide what precision to use.
        // The default for `sprintf` is 6.
        // 1e-6 degree amounts to about 10cm, which is well enough for coordinates.
        // (See: https://www.rfc-editor.org/rfc/rfc7946#section-11.2)
        // So let's use 6 explicitly.
        return sprintf('POINT(%.6f %.6f)', $latitude, $longitude);
    }
}
