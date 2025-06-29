<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\Litteralis;

use App\Application\Integration\Litteralis\DTO\LitteralisCredentials;
use App\Infrastructure\Integration\IntegrationReport\CommonRecordEnum;
use App\Infrastructure\Integration\IntegrationReport\Reporter;

final class LitteralisExtractor
{
    private array $clients;

    private const MEASURES_ILIKE_ITEMS = [
        '%circulation interdite%',
        '%limitation de vitesse%',
        '%interruption de circulation%',
        '%interdiction de stationnement%',
    ];

    public function __construct(
        private LitteralisClientFactory $clientFactory,
    ) {
    }

    public function configure(array $enabledOrgs, LitteralisCredentials $credentials)
    {
        $clients = [];

        foreach ($enabledOrgs as $name) {
            $orgCredentials = $credentials->getCredentials($name);
            $baseUrl = $credentials->getBaseUrl($name);
            $clients[$name] = $this->clientFactory->create($orgCredentials, $baseUrl);
        }

        $this->clients = $clients;
    }

    private function getClient(string $name): LitteralisClient
    {
        if (empty($this->clients[$name])) {
            throw new \RuntimeException(\sprintf('Organization with name "%s" is not enabled', $name));
        }

        return $this->clients[$name];
    }

    public function extractFeaturesByRegulation(string $name, \DateTimeInterface $laterThan, Reporter $reporter): array
    {
        $client = $this->getClient($name);

        // On ne récupère que les features remplissant certaines conditions, en utilisant
        // la syntaxe CQL.
        // Lien utile : fonctions CQL : https://docs.geoserver.org/latest/en/user/filter/function_reference.html#filter-function-reference

        // Exemple : measures ILIKE 'circulation interdite' OR mesures ILIKE 'stationnement interdit' [...]
        $quotedMeasureIlikeItems = array_map(fn ($i) => \sprintf("'%s'", $i), self::MEASURES_ILIKE_ITEMS);
        $mesuresFilter = \sprintf('mesures ILIKE %s', implode(' OR mesures ILIKE ', $quotedMeasureIlikeItems));

        $arreteFinFilter = \sprintf("arretefin IS NULL OR arretefin >= '%s'", $laterThan->format(\DateTimeInterface::ISO8601));

        $cqlFilter = \sprintf('(%s) AND (%s)', $mesuresFilter, $arreteFinFilter);

        // On calcule des totaux qui seront affichés dans le rapport final

        $numTotalFeatures = $client->count(null, $reporter);
        $reporter->addCount(LitteralisRecordEnum::COUNT_TOTAL_FEATURES->value, $numTotalFeatures);

        $numMatchingFeatures = $client->count($cqlFilter, $reporter);
        $reporter->addCount(LitteralisRecordEnum::COUNT_MATCHING_FEATURES->value, $numMatchingFeatures);

        // On récupère les emprises et on les regroupe par arrêté
        $featuresByRegulation = [];
        $numExtractedFeatures = 0;
        $features = $client->fetchAllPaginated($cqlFilter, $reporter);

        foreach ($features as $feature) {
            $identifier = $feature['properties']['arretesrcid'];

            if (empty($feature['geometry'])) {
                // Parfois la 'geometry' est absente
                $reporter->addWarning(LitteralisRecordEnum::WARNING_MISSING_GEOMETRY->value, [
                    CommonRecordEnum::ATTR_REGULATION_ID->value => $identifier,
                    CommonRecordEnum::ATTR_URL->value => $feature['properties']['shorturl'],
                    CommonRecordEnum::ATTR_DETAILS->value => [
                        'idemprise' => $feature['properties']['idemprise'],
                    ],
                ]);
                continue;
            }

            // D'après la documentation Litteralis, les coordonnées sont en EPSG:4326.
            // Mais la 'geometry' n'a pas de 'crs' pour l'indiquer explicitement, comme requis par PostGIS.
            // Donc on rajoute le 'crs' nous-mêmes.
            $feature['geometry']['crs'] = [
                'type' => 'name',
                'properties' => ['name' => 'EPSG:4326'],
            ];

            // On évite tout conflit d'identifiant entre collectivités
            $uniqueIdentifier = \sprintf('%s#%s', $feature['properties']['collectiviteagenceid'], $identifier);

            $featuresByRegulation[$uniqueIdentifier][] = $feature;
            ++$numExtractedFeatures;
        }

        $reporter->addCount(LitteralisRecordEnum::COUNT_EXTRACTED_FEATURES->value, $numExtractedFeatures, ['regulationsCount' => \count($featuresByRegulation)]);
        $reporter->onExtract(json_encode($featuresByRegulation, JSON_UNESCAPED_UNICODE & JSON_UNESCAPED_SLASHES));

        return $featuresByRegulation;
    }
}
