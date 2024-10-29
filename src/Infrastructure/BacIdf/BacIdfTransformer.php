<?php

declare(strict_types=1);

namespace App\Infrastructure\BacIdf;

use App\Application\BacIdf\Command\ImportBacIdfRegulationCommand;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Period\SaveDailyRangeCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Application\User\Command\SaveOrganizationCommand;
use App\Application\User\Query\GetOrganizationBySiretQuery;
use App\Domain\Condition\Period\Enum\ApplicableDayEnum;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\User\Exception\OrganizationNotFoundException;

final class BacIdfTransformer
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private BacIdfCityProcessorInterface $cityProcessor,
    ) {
    }

    public function transform(array $row): BacIdfTransformerResult
    {
        $loc = ['regulation_identifier' => $row['ARR_REF']];

        $errors = $this->makeBasicChecks($row);

        if ($errors) {
            $errors = array_map(fn ($error) => [
                'loc' => empty($error['loc']) ? $loc : [...$loc, ...$error['loc']],
                ...array_diff_key($error, ['loc' => '']),
                'impact' => 'skip_regulation',
            ], $errors);

            return new BacIdfTransformerResult(null, $errors);
        }

        $generalInfo = new SaveRegulationGeneralInfoCommand();
        $generalInfo->identifier = $row['ARR_REF'];
        $generalInfo->category = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        $generalInfo->description = $row['ARR_NOM'];

        $date = $row['ARR_DUREE']['PERIODE_DEBUT']['$date'];

        if (!\is_string($date)) {
            return new BacIdfTransformerResult(null, [
                [
                    'loc' => [...$loc, 'fieldname' => 'ARR_DUREE.PERIODE_DEBUT.$date'],
                    'reason' => 'value_not_expected_type',
                    'value' => json_encode($date),
                    'expected_type' => 'string',
                    'explaination' => 'Probably a $numberLong, which contains inconsistent data (such as dates ranging from 632 to 2040...)',
                    'impact' => 'skip_regulation',
                ],
            ]);
        }

        $generalInfo->startDate = new \DateTimeImmutable($date); // $date already contains the timezone (UTC)

        $inseeCode = $row['ARR_COMMUNE']['ARR_INSEE'];
        $siret = $this->cityProcessor->getSiretFromInseeCode($inseeCode);

        if (!$siret) {
            $errors[] = [
                'loc' => [...$loc, 'fieldname' => 'ARR_COMMUNE.ARR_INSEE'],
                'reason' => 'no_siret_found',
                'insee_code' => $inseeCode,
                'impact' => 'skip_regulation',
            ];

            return new BacIdfTransformerResult(null, $errors);
        }

        $organization = null;
        $organizationCommand = null;

        try {
            $organization = $this->queryBus->handle(new GetOrganizationBySiretQuery($siret));
        } catch (OrganizationNotFoundException) {
            $organizationCommand = new SaveOrganizationCommand();
            $organizationCommand->siret = $siret;
            $organizationCommand->name = \sprintf('Mairie de %s', $row['ARR_COMMUNE']['ARR_VILLE']);
        }

        $measureCommands = [];
        $errors = [];

        foreach ($row['REG_CIRCULATION'] as $index => $regCirculation) {
            $circReg = $regCirculation['CIRC_REG'];

            if (!\array_key_exists('REG_RESTRICTION', $circReg)) {
                $errors[] = [
                    'loc' => [...$loc, 'fieldname' => "REG_CIRCULATION.$index.CIRC_REG.REG_RESTRICTION"],
                    'reason' => 'value_absent',
                    'impact' => 'skip_measure',
                ];
                continue;
            }

            if ($circReg['REG_RESTRICTION'] !== true) {
                $errors[] = [
                    'loc' => [...$loc, 'fieldname' => "REG_CIRCULATION.$index.CIRC_REG.REG_RESTRICTION"],
                    'reason' => 'value_not_expected',
                    'value' => $circReg['REG_RESTRICTION'],
                    'expected' => true,
                    'impact' => 'skip_measure',
                ];
                continue;
            }

            $measureCommand = new SaveMeasureCommand();
            $measureCommand->type = MeasureTypeEnum::NO_ENTRY->value;
            $measureCommand->vehicleSet = $this->parseVehicleSetCommand($regCirculation);

            foreach ($circReg['REG_VOIES'] as $regVoieIndex => $regVoie) {
                if (\count($regVoie['VOIE_GEOJSON']['features']) === 0) {
                    $errors[] = [
                        'loc' => [...$loc, 'fieldname' => "REG_CIRCULATION.$index.CIRC_REG.REG_VOIES.$regVoieIndex.VOIE_GEOJSON.features"],
                        'reason' => 'array_empty',
                        'explaination' => 'Probably a road-less POI such as public square or roundabout',
                        'impact' => 'skip_location',
                    ];
                    continue;
                }

                $locationCommand = $this->parseLocation($row, $regVoie);

                $measureCommand->locations[] = $locationCommand;
            }

            if (\count($measureCommand->locations) === 0) {
                $errors[] = [
                    'loc' => [...$loc, 'fieldname' => \sprintf('measures.%d', $index)],
                    'reason' => 'no_locations_gathered',
                    'impact' => 'skip_measure',
                ];
                continue;
            }

            $periodCommands = $this->parsePeriods($circReg, startDate: $generalInfo->startDate);

            foreach ($periodCommands as $periodCommand) {
                $measureCommand->periods[] = $periodCommand;
            }

            $measureCommands[] = $measureCommand;
        }

        if (\count($measureCommands) === 0) {
            $errors[] = [
                'loc' => $loc,
                'reason' => 'no_measures_found',
                'impact' => 'skip_regulation',
            ];
        }

        if (\count($errors) > 0) {
            return new BacIdfTransformerResult(null, $errors);
        }

        $command = new ImportBacIdfRegulationCommand($generalInfo, $measureCommands);

        return new BacIdfTransformerResult($command, [], $organization, $organizationCommand);
    }

    private function makeBasicChecks(array $row): ?array
    {
        if (empty($row['REG_TYPE'])) {
            return [
                [
                    'loc' => ['fieldname' => 'REG_TYPE'],
                    'reason' => 'value_absent',
                ],
            ];
        }

        if ($row['REG_TYPE'] !== 'CIRCULATION') {
            return [
                [
                    'loc' => ['fieldname' => 'REG_TYPE'],
                    'reason' => 'value_not_expected',
                    'value' => $row['REG_TYPE'],
                    'expected' => 'CIRCULATION',
                ],
            ];
        }

        foreach ($row['REG_CIRCULATION'] as $index => $regCirculation) {
            if (!\array_key_exists('REG_VOIES', $regCirculation['CIRC_REG'])) {
                return [
                    [
                        'loc' => ['fieldname' => "REG_CIRCULATION.$index.CIRC_REG.REG_VOIES"],
                        'reason' => 'value_absent',
                        'explaination' => 'most likely a full-city regulation',
                    ],
                ];
            }

            foreach ($regCirculation['CIRC_REG']['REG_VOIES'] as $regVoie) {
                if (empty($regVoie['VOIE_GEOJSON'])) {
                    return [
                        [
                            'loc' => ['fieldname' => "REG_CIRCULATION.$index.CIRC_REG.REG_VOIES.VOIE_GEOJSON"],
                            'reason' => 'value_absent',
                            'explaination' => 'most likely an unclean case of full-city regulation',
                        ],
                    ];
                }
            }
        }

        $temporality = $row['ARR_DUREE']['ARR_TEMPORALITE'];

        if ($temporality !== 'PERMANENT') {
            return [
                [
                    'loc' => ['fieldname' => 'ARR_DUREE.ARR_TEMPORALITE'],
                    'reason' => 'value_not_expected',
                    'value' => $temporality,
                    'expected' => 'PERMANENT',
                ],
            ];
        }

        return null;
    }

    private function parseVehicleSetCommand(array $regCirculation): SaveVehicleSetCommand
    {
        $vehicleSetCommand = new SaveVehicleSetCommand();

        $isHeavyGoodsVehicle = false;
        $isDimensions = false;

        if (isset($regCirculation['CIRC_VEHICULES'])) {
            $vehicules = $regCirculation['CIRC_VEHICULES'];

            // NOTE: Most of the time, BAC-IDF think in terms of "which vehicles are forbidden".
            // But DiaLog thinks in terms of "what are the maximum allowed characteristics".
            // So, most of the time their 'min' become a 'max' for us.
            // Some decrees in the data use "max" values, but they are always a mistake and should be considered as "min" values.

            if (isset($vehicules['VEH_POIDS'])) {
                $vehPoids = $vehicules['VEH_POIDS'];

                if (isset($vehPoids['VEH_PTAC_MIN'])) {
                    $vehicleSetCommand->heavyweightMaxWeight = $vehPoids['VEH_PTAC_MIN'];
                    $isHeavyGoodsVehicle = true;
                }
                if (isset($vehPoids['VEH_PTAC_MAX'])) {
                    $vehicleSetCommand->heavyweightMaxWeight = $vehPoids['VEH_PTAC_MAX'];
                    $isHeavyGoodsVehicle = true;
                }
            }

            if (isset($vehicules['VEH_DIMENSION'])) {
                $dimensions = $vehicules['VEH_DIMENSION'];

                if (isset($dimensions['VEH_LARG_MIN']) && $dimensions['VEH_LARG_MIN'] > 0) {
                    $vehicleSetCommand->maxWidth = $dimensions['VEH_LARG_MIN'];
                    $isDimensions = true;
                }
                if (isset($dimensions['VEH_LARG_MAX']) && $dimensions['VEH_LARG_MAX'] > 0) {
                    $vehicleSetCommand->maxWidth = $dimensions['VEH_LARG_MAX'];
                    $isDimensions = true;
                }

                if (isset($dimensions['VEH_LONG_MIN']) && $dimensions['VEH_LONG_MIN'] > 0) {
                    $vehicleSetCommand->maxLength = $dimensions['VEH_LONG_MIN'];
                    $isDimensions = true;
                }
                if (isset($dimensions['VEH_LONG_MAX']) && $dimensions['VEH_LONG_MAX'] > 0) {
                    $vehicleSetCommand->maxLength = $dimensions['VEH_LONG_MAX'];
                    $isDimensions = true;
                }

                if (isset($dimensions['VEH_HAUT_MIN']) && $dimensions['VEH_HAUT_MIN'] > 0) {
                    $vehicleSetCommand->maxHeight = $dimensions['VEH_HAUT_MIN'];
                    $isDimensions = true;
                }
                if (isset($dimensions['VEH_HAUT_MAX']) && $dimensions['VEH_HAUT_MAX'] > 0) {
                    $vehicleSetCommand->maxHeight = $dimensions['VEH_HAUT_MAX'];
                    $isDimensions = true;
                }
            }
        }

        $vehicleSetCommand->allVehicles = !$isHeavyGoodsVehicle && !$isDimensions;

        if ($isHeavyGoodsVehicle) {
            $vehicleSetCommand->restrictedTypes[] = VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value;
        }

        if ($isDimensions) {
            $vehicleSetCommand->restrictedTypes[] = VehicleTypeEnum::DIMENSIONS->value;
        }

        $exemptedTypes = [];
        $otherExemptedTypes = [];

        if (isset($regCirculation['CIRC_REG']['REG_EXCEPT'])) {
            $regExcept = $regCirculation['CIRC_REG']['REG_EXCEPT'];

            foreach ($regExcept as $value) {
                switch ($value) {
                    case 'VEHICULES DE SERVICES':
                        $otherExemptedTypes[] = 'Véhicules de services';
                        break;
                    case 'POMPIERS':
                        $otherExemptedTypes[] = 'Pompiers';
                        break;
                    case 'TRANSPORT DE DECHETS':
                        $otherExemptedTypes[] = 'Transport de déchets';
                        break;
                    case 'POLICE':
                        $otherExemptedTypes[] = 'Police';
                        break;
                    case 'SECOURS':
                        $exemptedTypes[] = VehicleTypeEnum::EMERGENCY_SERVICES->value;
                        break;
                    case 'OTHER':
                        $value = trim($regCirculation['CIRC_REG']['REG_EXCEPT_DESC'], "., \n\r\t\v");

                        if (str_starts_with($value, "Tous moteurs de quelque nature qu'ils soient")) {
                            // This one does not actually define an "exception"
                            break;
                        }

                        // Summarize the longest ones...

                        if (str_starts_with($value, 'Les véhicules de charges et de commerces dont le conducteur peut justifier sa présence ')) {
                            $value = 'Véhicules autorisés';
                        }

                        if (str_starts_with($value, "véhicules d'intervention des Services Publics de l'Etat ")) {
                            $value = 'Véhicules de services, transports en commun, livraisons';
                        }

                        if (str_starts_with($value, "éhicules d'intérêt général tels que définis")) { // sic: "éhicules"
                            $value = "Véhicules d'intérêt général, véhicules de démanagement, véhicules de transport de matériel de chantier";
                        }

                        // Clean up some of the values...
                        $substitutions = [
                            'Les véhicules de ces catégories pourront avoir accès à cette rue, uniquement pour des motifs obligatoires : ' => '',
                            'Les véhicules de ces catégories pourront avoir accès à cette rue, uniquement pour des motifs exceptionnels : ' => '',
                            'La circulation est interdite aux véhicules de plus de 3,5 tonnes, dans la rue Mahmoud Darwich, sauf aux véhicules de service,' => 'véhicules',
                        ];
                        foreach ($substitutions as $search => $replace) {
                            $value = str_replace($search, $replace, $value);
                        }

                        $otherExemptedTypes[] = $value;
                        break;
                }
            }
        }

        if ($otherExemptedTypes) {
            $exemptedTypes[] = VehicleTypeEnum::OTHER->value;
            $vehicleSetCommand->otherExemptedTypeText = $this->formatOtherExemptedTypes($otherExemptedTypes);
        }

        $vehicleSetCommand->exemptedTypes = $exemptedTypes;

        return $vehicleSetCommand;
    }

    private function formatOtherExemptedTypes(array $types): string
    {
        $types[0] = ucfirst($types[0]);

        foreach (\array_slice($types, 1) as $index => $type) {
            $types[$index + 1] = strtolower($type);
        }

        return implode(', ', $types);
    }

    private function parseLocation(array $row, array $regVoie): SaveLocationCommand
    {
        $locationCommand = new SaveLocationCommand();
        $locationCommand->roadType = RoadTypeEnum::LANE->value;

        $geometries = [];

        foreach ($regVoie['VOIE_GEOJSON']['features'] as $feature) {
            $geometries[] = $feature['geometry'];
        }

        $geometry = [
            'type' => 'GeometryCollection',
            'geometries' => $geometries,
        ];

        $locationCommand->namedStreet = new SaveNamedStreetCommand();
        $locationCommand->namedStreet->roadType = RoadTypeEnum::LANE->value;
        $locationCommand->namedStreet->cityCode = $row['ARR_COMMUNE']['ARR_INSEE'];
        $locationCommand->namedStreet->cityLabel = \sprintf('%s (%s)', $row['ARR_COMMUNE']['ARR_VILLE'], $row['ARR_COMMUNE']['ARR_CODE_POSTAL']);
        $locationCommand->namedStreet->roadName = $regVoie['VOIE_NAME'];
        $locationCommand->namedStreet->geometry = json_encode($geometry, JSON_THROW_ON_ERROR);

        return $locationCommand;
    }

    private function parsePeriods(array $circReg, \DateTimeInterface $startDate): array
    {
        $periodCommands = [];

        foreach ($circReg['PERIODE_JH'] as $periodItem) {
            $heureDeb = \array_key_exists('HEURE_DEB', $periodItem) ? $periodItem['HEURE_DEB'] : null;
            $heureFin = \array_key_exists('HEURE_FIN', $periodItem) ? $periodItem['HEURE_FIN'] : null;

            $isEveryDay = $periodItem['JOUR'] === [1, 2, 3, 4, 5, 6, 0];

            if ($isEveryDay && !$heureDeb && !$heureFin) {
                return [];
            }

            if ($isEveryDay && $heureDeb === '00:00' && $heureFin === '23:59') {
                return [];
            }

            $periodCommand = new SavePeriodCommand();
            $periodCommand->isPermanent = true;
            $periodCommand->startDate = $startDate;
            $periodCommand->startTime = $startDate;
            $periodCommand->endDate = null;
            $periodCommand->endTime = null;

            $days = $periodItem['JOUR'];

            // In Bac-IDF data, 0 = Sunday, 1 = Monday, ..., 6 = Saturday
            // But we do 0 = Monday, ..., 5 = Saturday, 6 = Sunday
            $days = array_map(fn ($day) => ($day + 6) % 7, $days);

            sort($days);

            if ($days === [0, 1, 2, 3, 4, 5, 6]) {
                $periodCommand->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
            } else {
                $periodCommand->recurrenceType = PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value;

                $applicableDays = [];

                foreach ($days as $dayIndex) {
                    $applicableDays[] = ApplicableDayEnum::getByIndex($dayIndex);
                }

                $dailyRangeCommand = new SaveDailyRangeCommand();
                $dailyRangeCommand->applicableDays = $applicableDays;
                $periodCommand->dailyRange = $dailyRangeCommand;
            }

            $timeSlotCommands = [];

            if ($heureDeb !== '00:00' && $heureFin !== '23:59') {
                $timeSlotCommand = new SaveTimeSlotCommand();
                $timeSlotCommand->startTime = \DateTimeImmutable::createFromFormat('H:i', $heureDeb, new \DateTimeZone('Europe/Paris'));
                $timeSlotCommand->endTime = \DateTimeImmutable::createFromFormat('H:i', $heureFin, new \DateTimeZone('Europe/Paris'));
                $timeSlotCommands[] = $timeSlotCommand;
            }

            $periodCommand->timeSlots = $timeSlotCommands;

            $periodCommands[] = $periodCommand;
        }

        return $periodCommands;
    }
}
