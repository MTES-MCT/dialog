<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\RoadsNumbersInterface;
use App\Domain\Regulation\Exception\RoadNumberNotFoundException;
use Http\Client\Exception\HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class IgnWfsRoadsNumbers implements RoadsNumbersInterface
{
    public function __construct(
        private string $ignWfsUrl,
        private HttpClientInterface $ignWfsClient,
    ) {
    }

    public function getRoadsNumbers(string $search, string $gestionnaire, string $roadType): array
    {
        $normalizedSearch = str_replace("'", "''", strtoupper($search));
        $query = [
            'SERVICE' => 'WFS',
            'REQUEST' => 'GetFeature',
            'VERSION' => '2.0.0',
            'OUTPUTFORMAT' => 'application/json',
            'TYPENAME' => 'BDTOPO_V3:route_numerotee_ou_nommee',
            'cql_filter' => sprintf("strStartsWith(numero, '%s')=true AND gestionnaire='%s' AND type_de_route='%s'", $normalizedSearch, $gestionnaire, $roadType),
            'PropertyName' => 'numero', 'geometrie'
        ];

        $response = $this->ignWfsClient->request('GET', $this->ignWfsUrl, [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => $query,
        ]);
        try {
            $body = $response->getContent(throw: true);
        } catch (HttpException $exc) {
            $message = sprintf('invalid response: %s', $exc->getMessage());
            throw new RoadNumberNotFoundException($message);
        }

        try {
            $data = json_decode($body, associative: true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exc) {
            $message = sprintf('invalid json: %s', $exc->getMessage());
            throw new RoadNumberNotFoundException($message);
        }

        $numeros = [];
        if (isset($data['features']) && !empty($data['features'])) {
            $i = 0;
            $totalFeatures = \count($data['features']);
            while ($i < $totalFeatures) {
                $feature = $data['features'][$i];
                if (isset($feature['properties']['numero'])) {
                    $numero = $feature['properties']['numero'];
                    $numeros[] = $numero;
                }
                ++$i;
            }
        }
        return $numeros;
    }
}
