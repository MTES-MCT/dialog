<?php

declare(strict_types=1);

namespace App\Infrastructure\EudonetParis;

use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;
use App\Application\GeocoderInterface;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Domain\EudonetParis\EudonetParisLocationItem;
use App\Domain\Geography\GeometryFormatter;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\LocationAddress;
use App\Domain\User\Organization;

final class EudonetParisTransformer
{
    private const ARRONDISSEMENT_REGEX = '/^(?<arrondissement>\d+)(er|e|ème|eme)\s+arrondissement$/i';

    public function __construct(
        private GeocoderInterface $geocoder,
        private GeometryFormatter $geometryFormatter,
    ) {
    }

    public function transform(array $row, Organization $organization): EudonetParisTransformerResult
    {
        $errors = [];
        $errorPrefix = sprintf('at regulation_order %s', $row['fields'][EudonetParisExtractor::ARRETE_ID]);

        $generalInfoCommand = $this->buildGeneralInfoCommand($row, $organization);

        $locationItems = [];
        $errors = [];

        foreach ($row['measures'] as $measureRow) {
            [$measureCommand, $error] = $this->buildMeasureCommand($measureRow);

            if (empty($measureCommand)) {
                $errors[] = sprintf('%s: skip measure: %s', $errorPrefix, $error);
                continue;
            }

            $vehicleSet = new SaveVehicleSetCommand();
            $vehicleSet->allVehicles = true;
            $measureCommand->vehicleSet = $vehicleSet;

            foreach ($measureRow['locations'] as $locationRow) {
                [$locationItem, $error] = $this->buildLocationItem($locationRow, $measureCommand);

                if (empty($locationItem)) {
                    $errors[] = sprintf('%s: skip location: %s', $errorPrefix, $error);
                    continue;
                }

                $locationItems[] = $locationItem;
            }
        }

        if (\count($locationItems) === 0) {
            $errors[] = sprintf('%s: skip: no locations were gathered', $errorPrefix);

            return new EudonetParisTransformerResult(null, $errors);
        }

        $command = new ImportEudonetParisRegulationCommand(
            $generalInfoCommand,
            $locationItems,
        );

        return new EudonetParisTransformerResult($command, $errors);
    }

    private function buildGeneralInfoCommand(array $row, Organization $organization): SaveRegulationGeneralInfoCommand
    {
        $command = new SaveRegulationGeneralInfoCommand();
        $command->identifier = $row['fields'][EudonetParisExtractor::ARRETE_ID];

        $type = $row['fields'][EudonetParisExtractor::ARRETE_TYPE];

        $command->category = RegulationOrderCategoryEnum::OTHER->value;
        $command->otherCategoryText = $type;

        // Adhere to character limit
        $command->description = mb_substr($row['fields'][EudonetParisExtractor::ARRETE_COMPLEMENT_DE_TITRE], 0, 255);

        $command->organization = $organization;

        $command->startDate = \DateTimeImmutable::createFromFormat(
            'Y/m/d H:i:s',
            $row['fields'][EudonetParisExtractor::ARRETE_DATE_DEBUT],
            new \DateTimeZone('Europe/Paris'),
        );

        $command->endDate = \DateTimeImmutable::createFromFormat(
            'Y/m/d H:i:s',
            $row['fields'][EudonetParisExtractor::ARRETE_DATE_FIN],
            new \DateTimeZone('Europe/Paris'),
        );

        return $command;
    }

    private function buildMeasureCommand(array $row): array
    {
        $name = $row['fields'][EudonetParisExtractor::MESURE_NOM];

        if (strtolower($name) !== 'circulation interdite') {
            $id = $row['fields'][EudonetParisExtractor::MESURE_ID];

            return [null, sprintf('at measure %s: unsupported measure type: %s', $id, $name)];
        }

        $command = new SaveMeasureCommand();
        $command->type = MeasureTypeEnum::NO_ENTRY->value;

        return [$command, null];
    }

    private function computeJunctionPoint(string $address, string $roadName): string
    {
        $coords = $this->geocoder->computeJunctionCoordinates($address, $roadName);

        return $this->geometryFormatter->formatPoint($coords->latitude, $coords->longitude);
    }

    private function buildLocationItem(array $row, SaveMeasureCommand $measureCommand): array
    {
        $errorPrefix = sprintf('at location %s', $row['fields'][EudonetParisExtractor::LOCALISATION_ID]);

        $locationItem = new EudonetParisLocationItem();

        $arrondissement = $row['fields'][EudonetParisExtractor::LOCALISATION_ARRONDISSEMENT];

        if (!preg_match(self::ARRONDISSEMENT_REGEX, $arrondissement, $matches)) {
            $error = sprintf(
                '%s: ARRONDISSEMENT "%s" did not have expected format "%s"',
                $errorPrefix,
                $arrondissement, self::ARRONDISSEMENT_REGEX,
            );

            return [null, $error];
        }

        $arrondissement = (int) $matches['arrondissement'];
        $postCode = sprintf('750%s', str_pad((string) $arrondissement, 2, '0', STR_PAD_LEFT));

        $porteSur = $row['fields'][EudonetParisExtractor::LOCALISATION_PORTE_SUR];
        $libelleVoie = $row['fields'][EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE];
        $libelleVoieDebut = $row['fields'][EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_DEBUT];
        $libelleVoieFin = $row['fields'][EudonetParisExtractor::LOCALISATION_LIBELLE_VOIE_FIN];
        $numAdresseDebut = $row['fields'][EudonetParisExtractor::LOCALISATION_N_ADRESSE_DEBUT];
        $numAdresseFin = $row['fields'][EudonetParisExtractor::LOCALISATION_N_ADRESSE_FIN];

        $fromHouseNumber = null;
        $toHouseNumber = null;
        $fromRoadName = null;
        $toRoadName = null;

        $hasStart = $numAdresseDebut || $libelleVoieDebut;
        $hasEnd = $numAdresseFin || $libelleVoieFin;

        if ($porteSur === 'La totalité de la voie' && $libelleVoie) {
            $roadName = $libelleVoie;
        } elseif (\in_array($porteSur, ['Une section', 'Une zone', 'Un axe']) && $libelleVoie && $hasStart && $hasEnd) {
            $roadName = $libelleVoie;
            $fromHouseNumber = $numAdresseDebut;
            $toHouseNumber = $numAdresseFin;
            $fromRoadName = $libelleVoieDebut;
            $toRoadName = $libelleVoieFin;
        } else {
            return [null, sprintf('%s: could not extract supported location info: %s', $errorPrefix, json_encode($row))];
        }

        $locationItem->address = (string) new LocationAddress(
            postCode: $postCode,
            city: 'Paris',
            roadName: $roadName,
        );

        if ($fromHouseNumber) {
            $locationItem->fromHouseNumber = $fromHouseNumber;
        } elseif ($fromRoadName) {
            $locationItem->fromPoint = $this->computeJunctionPoint($locationItem->address, $fromRoadName);
        }

        if ($toHouseNumber) {
            $locationItem->toHouseNumber = $toHouseNumber;
        } elseif ($toRoadName) {
            $locationItem->toPoint = $this->computeJunctionPoint($locationItem->address, $toRoadName);
        }

        $locationItem->measures[] = $measureCommand;

        return [$locationItem, null];
    }
}
