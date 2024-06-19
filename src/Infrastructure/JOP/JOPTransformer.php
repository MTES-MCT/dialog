<?php

declare(strict_types=1);

namespace App\Infrastructure\JOP;

use App\Application\JOP\Command\ImportJOPRegulationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Application\RoadGeocoderInterface;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Specification\CanUseRawGeoJSON;
use App\Domain\User\Organization;
use Psr\Log\LoggerInterface;

final class JOPTransformer
{
    public const JOP_REGULATION_ORDER_IDENTIFIER = 'JOP2024-ZONES';

    public function __construct(
        private LoggerInterface $logger,
        private RoadGeocoderInterface $roadGeocoder,
    ) {
    }

    public function transform(array $geoJSON, Organization $organization): ImportJOPRegulationCommand
    {
        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();
        $generalInfoCommand->identifier = self::JOP_REGULATION_ORDER_IDENTIFIER;
        $generalInfoCommand->organization = $organization;
        $generalInfoCommand->category = RegulationOrderCategoryEnum::EVENT->value;
        $generalInfoCommand->description = 'Zones réglementées dans le cadre des Jeux Olympiques et Paralympiques de Paris 2024 (JOP 2024)';

        $measureCommands = [];
        $startDates = [];
        $endDates = [];
        $tz = new \DateTimeZone('Europe/Paris');

        // Many features share the same polygon geometry.
        // Process each polygon exactly once to minimize overlaps and duplicates.
        $featuresByGeometry = groupBy($geoJSON['features'], keyFunc: fn ($feature) => json_encode($feature['geometry']));

        foreach ($featuresByGeometry as $geom => $featureList) {
            $areaGeometry = json_decode($geom, associative: true); // Polygon or MultiPolygon

            if (!$areaGeometry) {
                // Some rows have a null geometry.
                $this->logger->warning('data_issue', [
                    'issue' => 'geometry_missing',
                    'impact' => 'skip_feature',
                    'featureProperties' => array_map(fn ($f) => $f['properties'], $featureList),
                ]);
                continue;
            }

            $measureCommand = new SaveMeasureCommand();
            $measureCommand->type = MeasureTypeEnum::NO_ENTRY->value;
            $measureCommand->permissions[] = CanUseRawGeoJSON::PERMISSION_NAME;

            $areaType = $featureList[0]['properties']['TYPE_PERI']; // Features with a same geometry share the same TYPE_PERI
            $eventNames = [];

            foreach ($featureList as $feature) {
                $eventNames[] = $feature['properties']['EVENEMENT'];

                $startDate = \DateTimeImmutable::createFromFormat('Y/m/d H:i:s.v', $feature['properties']['DATE_DEBUT'], $tz);
                $endDate = \DateTimeImmutable::createFromFormat('Y/m/d H:i:s.v', $feature['properties']['DATE_FIN'], $tz);

                if ($endDate < $startDate) {
                    $this->logger->warning('data_issue', [
                        'issue' => 'end_date_before_start_date',
                        'impact' => 'skip_feature',
                        'properties' => $feature['properties'],
                    ]);
                    continue;
                }

                $periodCommand = new SavePeriodCommand();
                $periodCommand->startDate = $startDate;
                $periodCommand->startTime = $startDate;
                $periodCommand->endDate = $endDate;
                $periodCommand->endTime = $endDate;
                $periodCommand->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
                $periodCommand->dailyRange = null;
                $periodCommand->timeSlots = [];

                $startDates[] = $startDate;
                $endDates[] = $endDate;

                $measureCommand->periods[] = $periodCommand;
            }

            if (!$measureCommand->periods) {
                // All dates were wrong
                continue;
            }

            $label = implode(', ', $eventNames);

            if (\strlen($label) > 255) {
                $suffix = ' [...]';
                $label = substr($label, 0, 255 - \strlen($suffix)) . $suffix;
            }

            // CRS is missing in the data but required by PostGIS
            $areaGeometry['crs'] = ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']];
            $sectionsGeometryCollection = $this->roadGeocoder->findSectionsInArea(json_encode($areaGeometry));

            $locationCommand = new SaveLocationCommand();
            $locationCommand->roadType = RoadTypeEnum::RAW_GEOJSON->value;
            $rawGeoJSON = new SaveRawGeoJSONCommand();
            $rawGeoJSON->label = $label;
            $rawGeoJSON->geometry = $sectionsGeometryCollection;
            $locationCommand->rawGeoJSON = $rawGeoJSON;
            $measureCommand->addLocation($locationCommand);

            $vehicleSetCommand = new SaveVehicleSetCommand();
            $vehicleSetCommand->allVehicles = true;
            $vehicleSetCommand->exemptedTypes = [VehicleTypeEnum::EMERGENCY_SERVICES->value, VehicleTypeEnum::OTHER->value];
            $vehicleSetCommand->otherExemptedTypeText = sprintf('Zone %s', $areaType);
            $measureCommand->vehicleSet = $vehicleSetCommand;

            $measureCommands[] = $measureCommand;
        }

        $generalInfoCommand->startDate = min($startDates);
        $generalInfoCommand->endDate = max($endDates);

        return new ImportJOPRegulationCommand($generalInfoCommand, $measureCommands);
    }
}

function groupBy(array $items, callable $keyFunc)
{
    $result = [];

    foreach ($items as $item) {
        $result[$keyFunc($item)][] = $item;
    }

    return $result;
}
