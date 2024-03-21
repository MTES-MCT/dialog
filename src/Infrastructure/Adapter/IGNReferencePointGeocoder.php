<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\ReferencePointGeocoderInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Regulation\Repository\AdministratorRepositoryInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class IGNReferencePointGeocoder implements ReferencePointGeocoderInterface
{
    public function __construct(
        private HttpClientInterface $apiDscrClient,
        private AdministratorRepositoryInterface $administratorRepository,
    ) {
    }

    public function compute(
        string $administrator,
        string $roadNumber,
        string $direction,
        int $pointNumber = 0,
        ?int $abscissa = 0,
    ): Coordinates {
        $administrators = $this->administratorRepository->findAll();

        if (!$codeDepth = array_search($administrator, $administrators)) {
            throw new \Exception('Administrator not found');
        }

        $query = [
            'code_dept' => $codeDepth,
            'route' => $roadNumber,
            'pr' => $pointNumber,
            'abscisse' => $abscissa,
            'cote' => $direction,
        ];

        $response = $this->apiDscrClient->request('GET', '/api/v1/pr/geocode', [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => $query,
        ]);

        $requestUrl = $response->getInfo()['url'];
        $errorMsgPrefix = sprintf('requesting %s', $requestUrl);

        if ($response->getStatusCode() >= 500) {
            $message = sprintf('%s: server error: HTTP %d', $errorMsgPrefix, $response->getStatusCode());
            throw new GeocodingFailureException($message);
        }

        if ($response->getStatusCode() >= 400) {
            $message = sprintf('%s: client error: HTTP %d', $errorMsgPrefix, $response->getStatusCode());
            throw new GeocodingFailureException($message);
        }

        if ($response->getStatusCode() >= 300) {
            $message = sprintf('%s: too many redirects: HTTP %d', $errorMsgPrefix, $response->getStatusCode());
            throw new GeocodingFailureException($message);
        }

        try {
            $data = $response->toArray(false);
        } catch (DecodingExceptionInterface $exc) {
            $message = sprintf('%s: invalid json: %s', $errorMsgPrefix, $exc->getMessage());
            throw new GeocodingFailureException($message);
        }

        if (!isset($data['features'][0]['geometry']['coordinates'])) {
            $message = sprintf('%s: key error: features', $errorMsgPrefix);
            throw new GeocodingFailureException($message);
        }

        $lonLat = $data['features'][0]['geometry']['coordinates'];

        return Coordinates::fromLonLat($lonLat[0], $lonLat[1]);
    }
}
