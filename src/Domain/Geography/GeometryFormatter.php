<?php

declare(strict_types=1);

namespace App\Domain\Geography;

class GeometryFormatter
{
    public function formatPoint(float $latitude, float $longitude): string
    {
        // See: https://github.com/jsor/doctrine-postgis
        // "Values provided for the properties must be in the WKT format."

        // WKT is "Well-known text": https://en.wikipedia.org/wiki/Well-known_text_representation_of_geometry

        // In WKT, 2D points are in the form: `POINT(X Y)`.
        // When using the standard EPSG:4326 projection, X is the longitude (East<>West axis) and Y is the latitude (North<>South axis).

        // NOTE: We need to decide what precision to use.
        // The default for `sprintf` is 6.
        // 1e-6° amounts to about 10cm, which is enough for coordinates.
        // (See: https://www.rfc-editor.org/rfc/rfc7946#section-11.2)
        // So we use 6 explicitly.

        return sprintf('POINT(%.6f %.6f)', $longitude, $latitude);
    }

    public function formatLine(float $fromLongitude, float $fromLatitude, float $toLongitude, float $toLatitude): string
    {
        return sprintf('LINESTRING(%.6f %.6f, %.6f %.6f)', $fromLongitude, $fromLatitude, $toLongitude, $toLatitude);
    }
}
