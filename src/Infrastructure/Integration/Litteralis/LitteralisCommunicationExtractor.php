<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\Litteralis;

use App\Application\Integration\Litteralis\DTO\LitteralisCredentials;
use App\Infrastructure\Integration\IntegrationReport\CommonRecordEnum;
use App\Infrastructure\Integration\IntegrationReport\Reporter;

/**
 * Extrait les données du flux WFS Litteralis "Communication" (une feature = une mesure),
 * les regroupe par emprise et produit le même format que LitteralisExtractor pour réutilisation
 * du LitteralisTransformer.
 *
 * @see LitteralisClient (couche LIcommunication via LitteralisCommunicationClientFactory)
 * @see LitteralisTransformer
 */
final class LitteralisCommunicationExtractor
{
    private array $clients = [];

    /** Filtres CQL sur le champ "mesure" (singulier) du flux Communication. */
    private const MESURE_ILIKE_ITEMS = [
        '%circulation interdite%',
        '%limitation de vitesse%',
        '%interruption de circulation%',
        '%interdiction de stationnement%',
    ];

    public function __construct(
        private LitteralisCommunicationClientFactory $clientFactory,
    ) {
    }

    public function configure(array $enabledOrgs, LitteralisCredentials $credentials): void
    {
        $this->clients = [];

        foreach ($enabledOrgs as $name) {
            $orgCredentials = $credentials->getCredentials($name);
            $client = $this->clientFactory->create($orgCredentials);
            $this->clients[$name] = $client;
        }
    }

    private function getClient(string $name): LitteralisClient
    {
        if (empty($this->clients[$name])) {
            throw new \RuntimeException(\sprintf('Organization with name "%s" is not enabled for Communication flux', $name));
        }

        return $this->clients[$name];
    }

    /**
     * Même contrat que LitteralisExtractor::extractFeaturesByRegulation.
     * Retourne un tableau [ uniqueIdentifier => [ feature synthétique par emprise ] ]
     * avec des propriétés "mesures" et "parametresmesures" construites à partir des champs
     * "mesure" et "parametresmesure" du flux Communication.
     *
     * @return array<string, list<array{geometry: array, properties: array}>>
     */
    public function extractFeaturesByRegulation(string $name, \DateTimeInterface $laterThan, Reporter $reporter): array
    {
        $client = $this->getClient($name);

        $quotedItems = array_map(fn (string $i) => \sprintf("'%s'", $i), self::MESURE_ILIKE_ITEMS);
        $mesureFilter = \sprintf('mesure ILIKE %s', implode(' OR mesure ILIKE ', $quotedItems));
        $arreteFinFilter = \sprintf("arretefin IS NULL OR arretefin >= '%s'", $laterThan->format(\DateTimeInterface::ISO8601));
        $cqlFilter = \sprintf('(%s) AND (%s)', $mesureFilter, $arreteFinFilter);

        $numTotalFeatures = $client->count(null, $reporter);
        $reporter->addCount(LitteralisRecordEnum::COUNT_TOTAL_FEATURES->value, $numTotalFeatures);

        $numMatchingFeatures = $client->count($cqlFilter, $reporter);
        $reporter->addCount(LitteralisRecordEnum::COUNT_MATCHING_FEATURES->value, $numMatchingFeatures);

        $features = $client->fetchAllPaginated($cqlFilter, $reporter);

        // Grouper par (idarrete, idemprise) : dans le flux Communication une feature = une mesure
        $byRegulationAndEmprise = [];
        $numExtractedFeatures = 0;

        foreach ($features as $feature) {
            $props = $feature['properties'] ?? [];
            $identifier = $props['arretesrcid'] ?? '';
            $idemprise = $props['idemprise'] ?? null;

            if (empty($feature['geometry'])) {
                $reporter->addWarning(LitteralisRecordEnum::WARNING_MISSING_GEOMETRY->value, [
                    CommonRecordEnum::ATTR_REGULATION_ID->value => $identifier,
                    CommonRecordEnum::ATTR_URL->value => $props['shorturl'] ?? '',
                    CommonRecordEnum::ATTR_DETAILS->value => ['idemprise' => $idemprise],
                ]);
                continue;
            }

            $feature['geometry']['crs'] = [
                'type' => 'name',
                'properties' => ['name' => 'EPSG:4326'],
            ];

            $collectiviteagenceid = $props['collectiviteagenceid'] ?? $props['idagence'] ?? '';
            $empriseKey = \sprintf('%s#%s#%s', $collectiviteagenceid, $identifier, $idemprise);

            if (!isset($byRegulationAndEmprise[$empriseKey])) {
                $byRegulationAndEmprise[$empriseKey] = [];
            }
            $byRegulationAndEmprise[$empriseKey][] = $feature;
            ++$numExtractedFeatures;
        }

        // Construire une feature synthétique par emprise (format attendu par LitteralisTransformer)
        $featuresByRegulation = [];
        foreach ($byRegulationAndEmprise as $empriseKey => $measureFeatures) {
            $first = $measureFeatures[0];
            $firstProps = $first['properties'];

            $mesures = [];
            $parametresmesuresParts = [];

            foreach ($measureFeatures as $mf) {
                $p = $mf['properties'];
                $mesureName = trim((string) ($p['mesure'] ?? ''));
                if ($mesureName === '') {
                    continue;
                }
                $mesures[] = $mesureName;

                $parametresmesure = trim((string) ($p['parametresmesure'] ?? ''));
                if ($parametresmesure !== '') {
                    $items = $this->parseSeparatedString($parametresmesure, ';');
                    foreach ($items as $item) {
                        $parametresmesuresParts[] = $mesureName . ' | ' . $item;
                    }
                }
            }

            $syntheticProperties = $firstProps;
            $syntheticProperties['mesures'] = implode(';', $mesures);
            $syntheticProperties['parametresmesures'] = implode(' ; ', $parametresmesuresParts);
            // parametresemprise : présent dans le flux Communication
            $syntheticProperties['parametresemprise'] = $syntheticProperties['parametresemprise'] ?? '';

            $syntheticFeature = [
                'geometry' => $first['geometry'],
                'properties' => $syntheticProperties,
            ];

            $uniqueIdentifier = \sprintf('%s#%s', $firstProps['collectiviteagenceid'] ?? $firstProps['idagence'] ?? '', $firstProps['arretesrcid']);
            $featuresByRegulation[$uniqueIdentifier][] = $syntheticFeature;
        }

        $reporter->addCount(LitteralisRecordEnum::COUNT_EXTRACTED_FEATURES->value, $numExtractedFeatures, [
            'regulationsCount' => \count($featuresByRegulation),
        ]);
        $reporter->onExtract(json_encode($featuresByRegulation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $featuresByRegulation;
    }

    private function parseSeparatedString(string $string, string $sep): array
    {
        if ($string === '') {
            return [];
        }
        $rawValues = explode($sep, $string);
        $values = [];
        foreach ($rawValues as $v) {
            $cleaned = trim($v);
            if ($cleaned !== '') {
                $values[] = $cleaned;
            }
        }

        return $values;
    }
}
