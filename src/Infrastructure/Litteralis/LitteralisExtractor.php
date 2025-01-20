<?php

declare(strict_types=1);

namespace App\Infrastructure\Litteralis;

use App\Application\Litteralis\DTO\LitteralisCredentials;
use App\Infrastructure\IntegrationReport\CommonRecordEnum;
use App\Infrastructure\IntegrationReport\Reporter;

final class LitteralisExtractor
{
    private array $clients;

    public function __construct(
        private LitteralisClientFactory $clientFactory,
    ) {
    }

    public function configure(array $enabledOrgs, LitteralisCredentials $credentials)
    {
        $clients = [];

        foreach ($enabledOrgs as $name) {
            $orgCredentials = $credentials->getCredentials($name);
            $clients[$name] = $this->clientFactory->create($orgCredentials);
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

        $cqlFilter = "(mesures ILIKE '%circulation interdite%' OR mesures ILIKE '%limitation de vitesse%' OR mesures ILIKE '%interruption de circulation%') AND " . \sprintf(
            "(arretefin IS NULL OR arretefin >= '%s')",
            $laterThan->format(\DateTimeInterface::ISO8601),
        );

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
