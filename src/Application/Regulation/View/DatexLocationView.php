<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Geography\GeoJSON;
use App\Domain\Geography\GMLv3;

final class DatexLocationView
{
    public readonly array $gmlPosLists;

    public function __construct(
        public readonly string $roadName,
        public readonly string $geometry,
    ) {
        $posLists = [];

        $geom = json_decode($geometry, associative: true);

        if ($geom['type'] == 'LineString') {
            $posLists[] = GMLv3::toPosList(GeoJSON::parseLineString($geometry));
        } elseif ($geom['type'] === 'MultiLineString') {
            dump($geometry);
            foreach ($geom['coordinates'] as $coords) {
                $posLists[] = GMLv3::toPosList(GeoJSON::parseLineString(['coordinates' => $coords]));
            }
        } else {
            throw new \RuntimeException(sprintf('unexpected geometry type: %s', $geom['type']));
        }

        $this->gmlPosLists = $posLists;
    }
}
