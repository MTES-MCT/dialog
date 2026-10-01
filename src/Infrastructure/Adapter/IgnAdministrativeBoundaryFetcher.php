<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\AdministrativeBoundaryFetcherInterface;
use App\Application\Exception\AdministrativeBoundaryNotFoundException;
use App\Application\Exception\AdministrativeBoundaryUnavailableException;
use App\Application\Geography\View\FetchedAdministrativeBoundaryView;
use App\Domain\Organization\Enum\OrganizationCodeTypeEnum;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Récupère les contours des collectivités dans ADMIN EXPRESS COG, le référentiel de l'IGN
 * conforme au code officiel géographique, via le service WFS de la Géoplateforme.
 *
 * On utilise l'édition « CARTO », dont les contours sont généralisés : ils sont bien plus
 * légers (une région pèse moins d'1 Mo) pour une précision suffisante à l'échelle d'une collectivité.
 */
final class IgnAdministrativeBoundaryFetcher implements AdministrativeBoundaryFetcherInterface
{
    private const TYPE_NAME_TEMPLATE = 'ADMINEXPRESS-COG-CARTO.LATEST:%s';

    // Couche WFS et attribut portant le code du COG, par type de collectivité.
    private const LAYERS = [
        OrganizationCodeTypeEnum::INSEE->value => ['commune', 'code_insee'],
        OrganizationCodeTypeEnum::EPCI->value => ['epci', 'code_siren'],
        OrganizationCodeTypeEnum::DEPARTMENT->value => ['departement', 'code_insee'],
        OrganizationCodeTypeEnum::REGION->value => ['region', 'code_insee'],
    ];

    // Le contour est téléchargé pendant une requête web : on borne l'attente (inactivité et durée totale).
    private const TIMEOUT_SECONDS = 5;
    private const MAX_DURATION_SECONDS = 10;

    public function __construct(
        private HttpClientInterface $ignGeocoderClient,
    ) {
    }

    public function fetch(OrganizationCodeTypeEnum $codeType, string $code): FetchedAdministrativeBoundaryView
    {
        [$layer, $codeAttribute] = self::LAYERS[$codeType->value];

        try {
            $response = $this->ignGeocoderClient->request(
                'GET',
                '/wfs/ows',
                [
                    'query' => [
                        'SERVICE' => 'WFS',
                        'VERSION' => '2.0.0',
                        'REQUEST' => 'GetFeature',
                        'TYPENAMES' => \sprintf(self::TYPE_NAME_TEMPLATE, $layer),
                        'CQL_FILTER' => \sprintf("%s='%s'", $codeAttribute, str_replace("'", "''", $code)),
                        'SRSNAME' => 'EPSG:4326',
                        'OUTPUTFORMAT' => 'application/json',
                        'COUNT' => 1,
                    ],
                    'timeout' => self::TIMEOUT_SECONDS,
                    'max_duration' => self::MAX_DURATION_SECONDS,
                ],
            );

            $data = $response->toArray();
        } catch (ExceptionInterface $exc) {
            throw new AdministrativeBoundaryUnavailableException(\sprintf('Failed to fetch administrative boundary "%s" of type "%s": %s', $code, $codeType->value, $exc->getMessage()), previous: $exc);
        }

        $feature = $data['features'][0] ?? null;

        if ($feature === null) {
            throw new AdministrativeBoundaryNotFoundException($codeType->value, $code);
        }

        if (empty($feature['geometry'])) {
            throw new AdministrativeBoundaryUnavailableException(\sprintf('Administrative boundary "%s" of type "%s" has no geometry', $code, $codeType->value));
        }

        return new FetchedAdministrativeBoundaryView(
            name: $feature['properties']['nom_officiel'] ?? $code,
            geometry: json_encode($feature['geometry'], \JSON_THROW_ON_ERROR),
        );
    }
}
