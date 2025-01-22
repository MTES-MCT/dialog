<?php

declare(strict_types=1);

namespace App\Infrastructure\Integration\JOP;

use App\Application\Integration\JOP\Command\ImportJOPRegulationCommand;
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
use App\Domain\Regulation\Enum\RegulationSubjectEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Specification\CanUseRawGeoJSON;
use App\Domain\User\Organization;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class JOPTransformer
{
    public const JOP_REGULATION_ORDER_IDENTIFIER = 'JOP2024-ZONES';

    public function __construct(
        private LoggerInterface $logger,
        private RoadGeocoderInterface $roadGeocoder,
        private TranslatorInterface $translator,
    ) {
    }

    public function transform(array $geoJSON, Organization $organization): ImportJOPRegulationCommand
    {
        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();
        $generalInfoCommand->identifier = self::JOP_REGULATION_ORDER_IDENTIFIER;
        $generalInfoCommand->organization = $organization;
        $generalInfoCommand->category = RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value;
        $generalInfoCommand->subject = RegulationSubjectEnum::EVENT->value;
        $generalInfoCommand->title = $this->translator->trans('jop.regulation_order.title');

        $measureCommands = [];
        $tz = new \DateTimeZone('Europe/Paris');

        // Many features share the same polygon geometry.
        // Process each polygon exactly once to minimize overlaps and duplicates.
        $featuresByGeometry = [];

        foreach ($geoJSON['features'] as $feature) {
            $featuresByGeometry[json_encode($feature['geometry'])][] = $feature;
        }

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

                $periodCommand = new SavePeriodCommand();
                $periodCommand->startDate = $startDate;
                $periodCommand->startTime = $startDate;
                $periodCommand->endDate = $endDate;
                $periodCommand->endTime = $endDate;
                $periodCommand->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
                $periodCommand->dailyRange = null;
                $periodCommand->timeSlots = [];

                $measureCommand->periods[] = $periodCommand;
            }

            $label = implode(', ', $eventNames);

            $sectionsGeometryCollection = $this->roadGeocoder->findSectionsInArea(
                json_encode($areaGeometry),
                excludeTypes: [$this->roadGeocoder::HIGHWAY],
            );

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
            $vehicleSetCommand->otherExemptedTypeText = \sprintf('Zone %s', $areaType);
            $measureCommand->vehicleSet = $vehicleSetCommand;

            $measureCommands[] = $measureCommand;
        }

        return new ImportJOPRegulationCommand($generalInfoCommand, $measureCommands);
    }
}
