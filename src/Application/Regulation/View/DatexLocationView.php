<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class DatexLocationView
{
    public readonly string $postList;

    public function __construct(
        public readonly string $address,
        public readonly ?string $fromHouseNumber,
        public readonly ?string $toHouseNumber,
        readonly ?string $gmlGeometry,
    ) {
        // Convert from Postgres GML output to only postList
        // See: https://postgis.net/docs/manual-3.4/ST_AsGML.html
        $xml = new \DOMDocument();
        $xml->loadXML($gmlGeometry, \LIBXML_NOBLANKS);
        $this->postList = $xml->firstElementChild->firstElementChild->textContent;
    }
}
