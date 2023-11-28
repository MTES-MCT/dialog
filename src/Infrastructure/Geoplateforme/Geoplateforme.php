<?php

declare(strict_types=1);

namespace App\Infrastructure\Geoplateforme;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Geoplateforme
{
    public function __construct(
        private HttpClientInterface $geoPlateformeHttpClient,
    ) {
    }

    public function fetchInformations(): string
    {
        $response = $this->geoPlateformeHttpClient->request(
            'GET',
            'https://data.geopf.fr/wfs/ows?SERVICE=WFS&REQUEST=GetFeature&VERSION=2.0.0&OUTPUTFORMAT=application/json&TYPENAME=BDADRESSE_BDD_WLD_WGS84G_20190208:commune&cql_filter=DWITHIN(geom,%20Point(48.941123%202.133123),0.01,kilometers)'
        );

        $statusCode = $response->getStatusCode();
        $content = $response->getContent();
        $result = json_decode($content) ;


        //return $result->features[0]->geometry->type;
        return $content;
    }
}
