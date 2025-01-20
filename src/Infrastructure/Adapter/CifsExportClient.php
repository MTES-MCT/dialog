<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Cifs\CifsExportClientInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CifsExportClient implements CifsExportClientInterface
{
    public function __construct(
        private HttpClientInterface $dialogHttpClient,
    ) {
    }

    public function getIncidentsCount(): int
    {
        $response = $this->dialogHttpClient->request('GET', '/api/regulations/cifs.xml');
        $xml = new \DOMDocument();
        $xml->loadXML($response->getContent(), \LIBXML_NOBLANKS);

        return $xml->getElementsByTagName('incident')->count();
    }
}
