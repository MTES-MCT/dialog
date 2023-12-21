<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

final class EudonetParisExtractor
{
    // Eudonet Paris table fields
    // Values come from Eudonet Paris' MetaInfos, see: https://eudonet.apps.paris.fr/eudoapi/eudoapidoc/swaggerui/#!/MetaInfos/post_MetaInfos

    // ARRETE fields
    public const ARRETE_TAB_ID = 1100;
    public const ARRETE_ID = 1101;
    public const ARRETE_COMPLEMENT_DE_TITRE = 1102;
    public const ARRETE_TYPE = 1108;
    public const ARRETE_DATE_DEBUT = 1109;
    public const ARRETE_DATE_FIN = 1110;

    // MESURE fields
    public const MESURE_TAB_ID = 1200;
    public const MESURE_ID = 1201;
    public const MESURE_NOM = 1202;
    public const MEASURE_NOM_CIRCULATION_INTERDITE_DB_VALUE = '103';

    // LOCALISATION fields
    public const LOCALISATION_TAB_ID = 2700;
    public const LOCALISATION_ID = 2701;
    public const LOCALISATION_PORTE_SUR = 2705;
    public const LOCALISATION_LIBELLE_VOIE = 2710;
    public const LOCALISATION_LIBELLE_VOIE_DEBUT = 2730;
    public const LOCALISATION_LIBELLE_VOIE_FIN = 2740;
    public const LOCALISATION_N_ADRESSE_DEBUT = 2720;
    public const LOCALISATION_N_ADRESSE_FIN = 2737;

    // Operators
    // See: https://eudonet.apps.paris.fr/eudoapi/eudoapidoc/lexique_FR.html
    public const EQUALS = 0;
    public const AND = 1;
    public const GREATER_THAN = 3;
    public const NOT_IN_LIST = 15;

    // ARRETE_TYPE values
    public const TEMPORAIRE = 8;

    public function __construct(
        private EudonetParisClient $eudonetParisClient,
    ) {
    }

    public function iterExtract(\DateTimeInterface $laterThanUTC, array $ignoreIDs = []): \Iterator
    {
        $whereCustoms = [
            [
                'Criteria' => [
                    'Field' => $this::ARRETE_TYPE,
                    'Operator' => $this::EQUALS,
                    'Value' => $this::TEMPORAIRE,
                ],
            ],
            [
                'Criteria' => [
                    'Field' => $this::ARRETE_DATE_FIN,
                    'Operator' => $this::GREATER_THAN,
                    'Value' => \DateTimeImmutable::createFromInterface($laterThanUTC)->setTimezone(new \DateTimeZone('Europe/Paris'))->format('Y/m/d H:i:s'),
                ],
                'InterOperator' => $this::AND,
            ],
        ];

        if (\count($ignoreIDs) > 0) {
            $whereCustoms[] = [
                'Criteria' => [
                    'Field' => $this::ARRETE_ID,
                    'Operator' => $this::NOT_IN_LIST,
                    'Value' => join(';', $ignoreIDs),
                ],
                'InterOperator' => $this::AND,
            ];
        }

        $regulationOrderRows = $this->eudonetParisClient->search(
            tabId: $this::ARRETE_TAB_ID,
            listCols: [
                self::ARRETE_ID,
                self::ARRETE_COMPLEMENT_DE_TITRE,
                self::ARRETE_TYPE,
                self::ARRETE_DATE_DEBUT,
                self::ARRETE_DATE_FIN,
            ],
            whereCustom: ['WhereCustoms' => $whereCustoms],
        );

        foreach ($regulationOrderRows as $regulationOrderRow) {
            $row = ['fileId' => $regulationOrderRow['fileId'], 'fields' => $regulationOrderRow['fields'], 'measures' => []];

            $mesureRows = $this->eudonetParisClient->search(
                tabId: $this::MESURE_TAB_ID,
                listCols: [
                    $this::MESURE_ID,
                    $this::MESURE_NOM,
                ],
                whereCustom: [
                    'WhereCustoms' => [
                        [
                            'Criteria' => [
                                'Field' => $this::ARRETE_TAB_ID,
                                'Operator' => $this::EQUALS,
                                'Value' => $regulationOrderRow['fileId'],
                            ],
                        ],
                        [
                            'Criteria' => [
                                'Field' => $this::MESURE_NOM,
                                'Operator' => $this::EQUALS,
                                'Value' => $this::MEASURE_NOM_CIRCULATION_INTERDITE_DB_VALUE,
                            ],
                            'InterOperator' => $this::AND,
                        ],
                    ],
                ],
            );

            foreach ($mesureRows as $mesureRow) {
                $measureRow = ['fileId' => $mesureRow['fileId'], 'fields' => $mesureRow['fields'], 'locations' => []];

                $locationRows = $this->eudonetParisClient->search(
                    tabId: $this::LOCALISATION_TAB_ID,
                    listCols: [
                        $this::LOCALISATION_ID,
                        $this::LOCALISATION_PORTE_SUR,
                        $this::LOCALISATION_LIBELLE_VOIE,
                        $this::LOCALISATION_LIBELLE_VOIE_DEBUT,
                        $this::LOCALISATION_LIBELLE_VOIE_FIN,
                        $this::LOCALISATION_N_ADRESSE_DEBUT,
                        $this::LOCALISATION_N_ADRESSE_FIN,
                    ],
                    whereCustom: [
                        'Criteria' => [
                            'Field' => $this::MESURE_TAB_ID,
                            'Operator' => $this::EQUALS,
                            'Value' => $mesureRow['fileId'],
                        ],
                    ],
                );

                foreach ($locationRows as $locationRow) {
                    $measureRow['locations'][] = ['fileId' => $locationRow['fileId'], 'fields' => $locationRow['fields']];
                }

                $row['measures'][] = $measureRow;
            }

            yield $row;
        }
    }
}
