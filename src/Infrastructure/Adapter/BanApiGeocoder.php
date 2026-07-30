<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\HouseNumberGeocoderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BanApiGeocoder implements HouseNumberGeocoderInterface
{
    public function __construct(
        private HttpClientInterface $banLookupClient,
    ) {
    }

    public function findHouseNumbers(string $roadBanId): array
    {
        if (!$roadBanId) {
            return [];
        }

        // L'API BAN expose, pour chaque voie, la liste de ses numéros via l'endpoint /lookup/{id}.
        // See: https://plateforme.adresse.data.gouv.fr/lookup/{id}
        $response = $this->banLookupClient->request('GET', \sprintf('/lookup/%s', rawurlencode($roadBanId)), [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        try {
            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $data = $response->toArray(throw: true);

            if (!\array_key_exists('numeros', $data) || !\is_array($data['numeros'])) {
                return [];
            }

            $houseNumbers = [];

            foreach ($data['numeros'] as $numero) {
                if (!isset($numero['numero'])) {
                    continue;
                }

                $suffixe = isset($numero['suffixe']) && $numero['suffixe'] ? strtolower((string) $numero['suffixe']) : '';
                $houseNumbers[] = \sprintf('%s%s', $numero['numero'], $suffixe);
            }

            // Tri naturel : 1, 2, 6bis, 10... (et non 1, 10, 2)
            usort($houseNumbers, 'strnatcasecmp');

            return array_values(array_unique($houseNumbers));
        } catch (\Exception $exc) {
            \Sentry\captureException($exc);

            return [];
        }
    }
}
