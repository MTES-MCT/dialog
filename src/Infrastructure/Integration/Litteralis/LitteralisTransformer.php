<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\Litteralis;

use App\Application\Integration\Litteralis\Command\ImportLitteralisRegulationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationSubjectEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Specification\CanUseRawGeoJSON;
use App\Domain\User\Organization;
use App\Infrastructure\Integration\IntegrationReport\CommonRecordEnum;
use App\Infrastructure\Integration\IntegrationReport\Reporter;

final readonly class LitteralisTransformer
{
    private const MEASURE_MAP = [
        'SOGELINK - Circulation interdite' => MeasureTypeEnum::NO_ENTRY->value,
        'Circulation interdite' => MeasureTypeEnum::NO_ENTRY->value,
        'Limitation tonnage (Arrêté Permanent)' => MeasureTypeEnum::NO_ENTRY->value,
        'SOGELINK - Limitation de vitesse' => MeasureTypeEnum::SPEED_LIMITATION->value,
        'Limitation de vitesse' => MeasureTypeEnum::SPEED_LIMITATION->value,
        'Limitation de vitesse (Arrêté Permanent)' => MeasureTypeEnum::SPEED_LIMITATION->value,
        'Interdiction de stationnement' => MeasureTypeEnum::PARKING_PROHIBITED->value,
    ];

    public function __construct(
        private RoadGeocoderInterface $roadGeocoder,
        private LitteralisPeriodParser $periodParser,
    ) {
    }

    public function transform(
        Reporter $reporter,
        string $identifier,
        array $regulationFeatures,
        Organization $organization,
    ): ?ImportLitteralisRegulationCommand {
        if (!$regulationFeatures) {
            // C'est un cas théorique, en pratique l'extractor ne passera pas de liste vide.
            return null;
        }

        // Les données Litteralis viennent directement sous forme de features GeoJSON
        // https://geojson.org/
        // Chaque feature représente une "emprise". Une emprise est un assemblage de mesures et de localisations.
        // La feature contient la "geometry" de l'emprise ainsi que des "properties" qui contiennent les informations
        // sur les mesures et les localisations.
        // Les emprises font référence à un arrêté, on crée donc un arrêté DiaLog par tel arrêté.
        // L'extractor a déjà rassemblé les features par arrêté.
        // On parse d'abord les informations générales de l'arrêté DiaLog (1).
        // Ensuite on traite le reste des "properties" pour construire les mesures de l'arrêté DiaLog (2).

        // (1) Parsing des informations générales

        $properties = $regulationFeatures[0]['properties'];

        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();
        $generalInfoCommand->identifier = $identifier;
        $this->setCategory($generalInfoCommand, $properties);
        $this->setSubject($generalInfoCommand, $properties);
        $generalInfoCommand->title = $this->buildDescription($properties);
        $generalInfoCommand->organization = $organization;

        // (2) Parsing des mesures et leur contenu

        $measureCommands = [];

        foreach ($regulationFeatures as $feature) {
            // Une feature (emprise) contient une seule "geometry" pouvant rassembler plusieurs endroits précisés dans la propriété 'localisations'.
            // On crée donc une seule SaveCommandLocation de type RawGeoJSON.
            // On l'assigne ensuite à chaque mesure présente dans la propriété 'mesures'.
            $locationCommand = $this->parseLocation($feature, $organization);
            $featureMeasureCommands = $this->parseMeasures($feature['properties'], $reporter);

            foreach ($featureMeasureCommands as $measureCommand) {
                $measureCommand->permissions[] = CanUseRawGeoJSON::PERMISSION_NAME;
                $measureCommand->addLocation($locationCommand);
                $measureCommands[] = $measureCommand;
            }
        }

        // On ne return pour cause d'erreur qu'à la toute fin pour détecter toutes les erreurs.
        if ($reporter->hasNewErrors()) {
            return null;
        }

        // Il se peut que l'arrêté ne contienne aucune mesure supportée par DiaLog.
        // Dans ce cas $measureCommands sera vide.
        // Ça n'est pas vraiment une erreur mais dans DiaLog un arrêté sans mesures n'est pas valide.
        // Il faut donc zapper cet arrêté.
        if (\count($measureCommands) === 0) {
            $reporter->addNotice(LitteralisRecordEnum::NOTICE_NO_MEASURES_FOUND->value, [
                CommonRecordEnum::ATTR_REGULATION_ID->value => $properties['arretesrcid'],
                CommonRecordEnum::ATTR_URL->value => $properties['shorturl'],
            ]);

            return null;
        }

        return new ImportLitteralisRegulationCommand($generalInfoCommand, $measureCommands);
    }

    private function setCategory(SaveRegulationGeneralInfoCommand $generalInfoCommand, array $properties): void
    {
        $generalInfoCommand->category = $properties['documenttype'] === 'ARRETE PERMANENT'
            ? RegulationOrderCategoryEnum::PERMANENT_REGULATION->value
            : RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value;
    }

    private function setSubject(SaveRegulationGeneralInfoCommand $generalInfoCommand, array $properties): void
    {
        $categoriesModeleValue = $properties['categoriesmodele'];
        $generalInfoCommand->subject = match ($categoriesModeleValue) {
            'Travaux' => RegulationSubjectEnum::ROAD_MAINTENANCE->value,
            'Evenements' => RegulationSubjectEnum::EVENT->value,
            default => RegulationSubjectEnum::OTHER->value,
        };

        if ($generalInfoCommand->subject === RegulationSubjectEnum::OTHER->value) {
            $generalInfoCommand->otherCategoryText = $categoriesModeleValue;
        }
    }

    private function buildDescription(array $properties): string
    {
        $description = $this->parseDescription($properties);

        return \sprintf('%s (URL : %s)', $description, $properties['shorturl']);
    }

    private function parseDescription(array $properties): string
    {
        $parameters = $this->parseRegulationParameters($properties);

        $description = $this->findParameterValue($parameters, 'Description des travaux');

        if ($description) {
            return $description;
        }

        $description = $this->findParameterValue($parameters, 'Description évènement');

        if ($description) {
            return $description;
        }

        return $properties['nommodele']; // Less descriptive but always present
    }

    private function parseRegulationParameters(array $properties): array
    {
        // 'parametresarrete' est une liste de 'KEY : VALUE' séparés par des ';'.
        // Exemple de valeur : "Date de réception de la demande : 18/12/2023 00:00:00 ; Date de début de l'arrêté : 10/01/2024 00:00:00 ; Date de fin de l'arrêté : 10/01/2024 00:00:00 ; Description des travaux : sur réseaux ou ouvrages d'eaux usées / assainissement ; Période des travaux : Du 18/01/2024 00:00:00 au 18/01/2024 00:00:00 ; ajout annexe : N ; chargé de MEP de la signalisation : Le demandeur de l'acte",
        $parameters = [];

        $items = $this->parseSeparatedString($properties['parametresarrete'], ';');

        foreach ($items as $item) {
            [$key, $value] = explode(' :', $item, 2);
            $value = trim($value);
            $parameters[] = [$key, $value];
        }

        return $parameters;
    }

    private function parseLocation(array $feature, Organization $organization): SaveLocationCommand
    {
        $properties = $feature['properties'];
        $label = trim($properties['localisations'] ?? 'Localisation sans description');

        // Selon la collectivité, la géométrie peut être de plusieurs sortes :
        // * (Cas par défaut) Un linéaire, sous forme de LINESTRING ou MULTILINESTRING => on importe tel quel.
        // * Un POLYGON ou un MULTIPOLYGON dessiné(s) par un agent dans Litteralis => On convertit en linéaire en s'aidant des tronçons de route de la BDTOPO.
        $geometry = json_encode($feature['geometry']);

        $sectionsGeometry = $this->roadGeocoder->convertPolygonRoadToLines($geometry);

        $locationCommand = new SaveLocationCommand();
        $locationCommand->organization = $organization;
        $locationCommand->roadType = RoadTypeEnum::RAW_GEOJSON->value;
        $locationCommand->rawGeoJSON = new SaveRawGeoJSONCommand();
        $locationCommand->rawGeoJSON->geometry = $sectionsGeometry;
        $locationCommand->rawGeoJSON->label = $label;

        return $locationCommand;
    }

    /**
     * @return SaveMeasureCommand[]
     */
    private function parseMeasures(array $properties, Reporter $reporter): array
    {
        // D'abord on rassemble les "paramètres" de chaque mesure.

        $parametersByMeasureName = $this->gatherMeasureParameters($properties, $reporter);

        // Ensuite, on traite chaque mesure en interprétant ses paramètres.

        $measureCommands = [];

        foreach ($parametersByMeasureName as $name => $parameters) {
            $measureCommand = new SaveMeasureCommand();
            $measureCommand->type = self::MEASURE_MAP[$name];

            if ($measureCommand->type === MeasureTypeEnum::SPEED_LIMITATION->value) {
                $measureCommand->maxSpeed = $this->parseMaxSpeed($properties, $parameters, $reporter);
            }

            $measureCommand->vehicleSet = $this->parseVehicleSet($parameters, $reporter);
            $measureCommand->periods = $this->periodParser->parsePeriods($parameters, $properties, $reporter);

            $measureCommands[] = $measureCommand;
        }

        return $measureCommands;
    }

    private function gatherMeasureParameters(array $properties, Reporter $reporter): array
    {
        // NOTE: Ces commentaires sont en français pour faciliter la compréhension.

        // Le champ 'mesures' d'une emprise contient une liste de noms de mesures, séparés par des ';'
        // Par exemple: 'Circulation alternée;Interdiction de dépasser;Interdiction de stationnement;Limitation de vitesse'
        $allMeasureNames = $this->parseSeparatedString($properties['mesures'], ';');

        // On ne garde que les mesures que l'on peut intégrer
        $measureNames = [];

        foreach ($allMeasureNames as $name) {
            if (!\array_key_exists($name, self::MEASURE_MAP)) {
                $reporter->addNotice(LitteralisRecordEnum::NOTICE_UNSUPPORTED_MEASURE->value, [
                    CommonRecordEnum::ATTR_REGULATION_ID->value => $properties['arretesrcid'],
                    CommonRecordEnum::ATTR_DETAILS->value => [
                        'name' => $name,
                        'idemprise' => $properties['idemprise'],
                    ],
                ]);
                continue;
            }

            $measureNames[] = $name;
        }

        // Pour chaque nom de mesure, il y a un ensemble correspondant de "paramètres" dans le champ 'parametresmesures' qui précisent la mesure.
        // Par exemple: 'Circulation alternée | type d'alternat : feux et K10 ; Circulation alternée | jours et horaires : de 20H00 à 6H00 ; Limitation de vitesse 4 | limite de vitesse : 30 km/h'

        // Le format est standardisé mais un peu complexe.
        // C'est une liste d'éléments au format 'NAME | KEY : VALUE', séparés par des point-virgules.
        // Le NAME correspond au nom de la mesure tel qu'il est présent dans le champ 'mesures'.
        $measureParameters = $this->parseSeparatedString($properties['parametresmesures'], ';');

        // D'après la documentation Litteralis, un numéro PEUT être ajouté au NAME.
        // Par exemple : "Interdiction de stationnement 3", ou encore "Limitation de vitesse 4".
        // En général il correspond à l'index démarrant à 1 de la mesure dans le champ 'mesures'.
        // Mais parfois ce n'est pas le cas... Par exemple :
        // * 'mesures' = 'SOGELINK - Interdiction de stationnement;SOGELINK - Circulation interdite'
        // * 'parametresmesures' = 'Interdiction de stationnement 2 | jours et horaires : de 08 h 00 à 18 h 00 ; SOGELINK - Circulation interdite | jours et horaires : de 08 h 00 à 18 h 00'
        // Ici 'Interdiction de stationnement' est en position 1 dans 'mesures', mais a le numéro 2 dans 'parametresmesures',
        // tandis que 'SOGELINK - Circulation interdite' qui est en position 2 n'a pas de numéro.
        // Comme ce cas est imprévisible, nous le traiterons comme une erreur.

        // On rassemble les paramètres par nom de mesure pour obtenir un array de ce type :
        // [
        //    'Circulation alternée' =>  ["type d'alternat : feux et K10", "jours et horaires : de 20H00 à 6H00"],
        //    'Limitation de vitesse' => ['limite de vitesse : 30 km/h'],
        // ]

        $parametersByMeasureName = [];

        foreach ($measureNames as $name) {
            $parametersByMeasureName[$name] = [];
        }

        foreach ($measureParameters as $param) {
            // Exemple : "Circulation interdite 2 | dérogation : urgences" -> ['Circulation interdite 2', 'dérogation : urgences']
            $parts = explode('|', $param, 2);

            if (\count($parts) !== 2) {
                $reporter->addError(LitteralisRecordEnum::ERROR_MEASURE_PARAMETER_MALFORMED->value, [
                    CommonRecordEnum::ATTR_REGULATION_ID->value => $properties['arretesrcid'],
                    CommonRecordEnum::ATTR_URL->value => $properties['shorturl'],
                    CommonRecordEnum::ATTR_DETAILS->value => [
                        'idemprise' => $properties['idemprise'],
                        'param' => $param,
                    ],
                ]);

                continue;
            }

            [$name, $item] = array_map(fn ($s) => trim($s), $parts);

            if (preg_match('/^(?P<name>[\s|\w]+) (?P<number>\d+)$/i', $name, $matches)) {
                // Si un numéro est indiqué, il doit correspondre au index commençant à 1 de la mesure dans le champ 'mesures'.
                $cleanedName = $matches['name'];
                $number = (int) $matches['number'];

                if (!\in_array($cleanedName, $measureNames)) {
                    continue;
                }

                $index = array_search($cleanedName, $allMeasureNames);

                if ($number !== $index + 1) {
                    // Le numéro indiqué ne correspond pas au 1-index de la mesure dans 'mesures'.
                    // On traite ce cas comme une erreur car on ne peut pas savoir à quelle mesure ces paramètres se rattachent.
                    $reporter->addError(LitteralisRecordEnum::ERROR_MEASURE_PARAMETER_INCONSISTENT_NUMBER->value, [
                        CommonRecordEnum::ATTR_REGULATION_ID->value => $properties['arretesrcid'],
                        CommonRecordEnum::ATTR_URL->value => $properties['shorturl'],
                        CommonRecordEnum::ATTR_DETAILS->value => [
                            'idemprise' => $properties['idemprise'],
                            'measureName' => $name,
                            'expected' => $index + 1,
                            'actual' => $number,
                        ],
                    ]);

                    continue;
                }

                $name = $cleanedName;
            } else {
                if (!\in_array($name, $measureNames)) {
                    continue;
                }
            }

            // Exemple : "dérogation : urgences" -> ['dérogation', 'urgences']
            [$key, $value] = explode(' : ', $item, 2);

            $parametersByMeasureName[$name][] = [$key, $value];
        }

        // Les 'parametresemprise' peuvent contenir des informations importantes comme des 'jours et horaires'
        // Ils ont le format 'KEY : VALUE' séparé par des ';', et concernent toutes les mesures
        $empriseParameters = $this->parseSeparatedString($properties['parametresemprise'], ';');

        foreach ($empriseParameters as $param) {
            [$key, $value] = $this->parseSeparatedString($param, ':');

            foreach ($measureNames as $name) {
                $parametersByMeasureName[$name][] = [$key, $value];
            }
        }

        return $parametersByMeasureName;
    }

    private function parseVehicleSet(array $parameters, Reporter $reporter): SaveVehicleSetCommand
    {
        $vehicleSetCommand = new SaveVehicleSetCommand();

        // Traitement des véhicules concernés

        // Exemple de valeur : "piétons, cycles,poids lourds,véhicules légers"
        $vehiculesConcernes = $this->parseSeparatedString($this->findParameterValue($parameters, 'véhicules concernés') ?? '', ',');

        $restrictedTypes = [];
        $otherRestrictedTypes = [];
        $otherRestrictedTypeText = null;

        foreach ($vehiculesConcernes as $value) {
            $vehicleType = match ($value) {
                'piétons' => VehicleTypeEnum::PEDESTRIANS->value,
                'cycles' => VehicleTypeEnum::BICYCLE->value,
                'poids lourds' => VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value,
                'véhicules de plus de 3.5 tonnes' => VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value,
                default => null,
            };

            if ($vehicleType === null) {
                $otherRestrictedTypes[] = $value;
                continue;
            }

            $restrictedTypes[] = $vehicleType;

            if ($vehicleType === VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value) {
                $vehicleSetCommand->heavyweightMaxWeight = 3.5;
            }
        }

        if (\count($otherRestrictedTypes) > 0) {
            $restrictedTypes[] = VehicleTypeEnum::OTHER->value;
            $otherRestrictedTypeText = implode(', ', $otherRestrictedTypes);
        }

        $vehicleSetCommand->restrictedTypes = $restrictedTypes;
        $vehicleSetCommand->otherRestrictedTypeText = $otherRestrictedTypeText;

        $vehicleSetCommand->allVehicles = empty($vehicleSetCommand->restrictedTypes);

        // Traitement des véhicules exemptés ("Sauf...")
        // Exemple de valeur : "véhicules de l'entreprise effectuant les travaux,véhicules de déménagement"
        // Le champ "dérogations" ne contient que des types inconnus de DiaLog, donc on met tout dans "Autre"
        $derogations = $this->findParameterValue($parameters, 'dérogations');
        $vehicleSetCommand->exemptedTypes = $derogations ? [VehicleTypeEnum::OTHER->value] : [];
        $vehicleSetCommand->otherExemptedTypeText = $derogations;

        return $vehicleSetCommand;
    }

    private function parseMaxSpeed(array $properties, array $parameters, Reporter $reporter): ?int
    {
        $value = $this->findParameterValue($parameters, 'limite de vitesse');

        if (!$value) {
            $reporter->addError(LitteralisRecordEnum::ERROR_MAX_SPEED_VALUE_MISSING->value, [
                CommonRecordEnum::ATTR_REGULATION_ID->value => $properties['arretesrcid'],
                CommonRecordEnum::ATTR_URL->value => $properties['shorturl'],
                CommonRecordEnum::ATTR_DETAILS->value => [
                    'idemprise' => $properties['idemprise'],
                    'mesures' => $properties['mesures'],
                ],
            ]);

            return null;
        }

        if (!preg_match('/^(?P<speed>\d+)/i', $value, $matches)) {
            $reporter->addError(LitteralisRecordEnum::ERROR_MAX_SPEED_VALUE_INVALID->value, [
                CommonRecordEnum::ATTR_REGULATION_ID->value => $properties['arretesrcid'],
                CommonRecordEnum::ATTR_URL->value => $properties['shorturl'],
                CommonRecordEnum::ATTR_DETAILS->value => [
                    'idemprise' => $properties['idemprise'],
                    'limite de vitesse' => $value,
                ],
            ]);

            return null;
        }

        return (int) $matches['speed'];
    }

    // Utilities

    private function findParameterValue(array $parameters, string $theKey): ?string
    {
        foreach ($parameters as [$key, $value]) {
            if ($key === $theKey) {
                return $value;
            }
        }

        return null;
    }

    private function parseSeparatedString(string $string, string $sep): array
    {
        if (!$string) {
            return [];
        }

        $rawValues = explode($sep, $string);

        $values = [];

        foreach ($rawValues as $v) {
            $cleanedValue = trim($v);

            if ($cleanedValue) {
                $values[] = $cleanedValue;
            }
        }

        return $values;
    }
}
