<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationSubjectEnum;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderTemplate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class RegulationOrderFixture extends Fixture implements DependentFixtureInterface
{
    public const TYPICAL_IDENTIFIER = 'FO1/2023';
    public const IDENTIFIER_CIFS = 'F/CIFS/2023';

    public function load(ObjectManager $manager): void
    {
        $typicalRegulationOrder = new RegulationOrder(
            uuid: '54eacea0-e1e0-4823-828d-3eae72b76da8',
            identifier: self::TYPICAL_IDENTIFIER,
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            title: 'Title 1',
            subject: RegulationSubjectEnum::EVENT->value,
        );

        $publishedRegulationOrder = new RegulationOrder(
            uuid: '2e5eb289-90c8-4c3f-8e7c-2e9e7de8948c',
            identifier: 'FO2/2023',
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            title: 'Title 2',
            subject: RegulationSubjectEnum::ROAD_MAINTENANCE->value,
            regulationOrderTemplate: $this->getReference('regulationOrderTemplate', RegulationOrderTemplate::class),
        );

        $regulationOrderPermanent = new RegulationOrder(
            uuid: 'c147cc20-ed02-4bd9-9f6b-91b67df296bd',
            identifier: 'FO3/2023',
            category: RegulationOrderCategoryEnum::PERMANENT_REGULATION->value,
            title: 'Title 3',
            subject: RegulationSubjectEnum::ROAD_MAINTENANCE->value,
        );

        $fullCityRegulationOrder = new RegulationOrder(
            uuid: '0658c568-dfbe-7c64-8000-303f7e2ae9b3',
            identifier: 'F2023/full-city',
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            title: 'Title 2',
            subject: RegulationSubjectEnum::ROAD_MAINTENANCE->value,
        );

        $regulationOrderNoLocations = new RegulationOrder(
            uuid: 'e589f277-ccd4-4364-967a-7e9db80e6d34',
            identifier: 'F2023/no-locations',
            title: 'Title 5 that is very long and will be truncated',
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            subject: RegulationSubjectEnum::OTHER->value,
            otherCategoryText: 'Dérogation préfectorale',
        );

        $regulationOrderNoMeasures = new RegulationOrder(
            uuid: '0650037e-8f8e-7f66-8000-c8ebe51493b9',
            identifier: 'FO14/2023',
            title: 'No measures',
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            subject: RegulationSubjectEnum::ROAD_MAINTENANCE->value,
        );

        $regulationOrderCifs = new RegulationOrder(
            uuid: '06549047-db9d-74bb-8000-754a6f2ff4c3',
            identifier: self::IDENTIFIER_CIFS,
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            title: 'Arrêté exporté vers CIFS',
            subject: RegulationSubjectEnum::ROAD_MAINTENANCE->value,
        );

        $outDatedRegulationOrderCifs = new RegulationOrder(
            uuid: 'edc8dd18-5f56-4684-b2ba-d18658d53518',
            identifier: 'F/OUTDATED/CIFS/2021',
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            subject: RegulationSubjectEnum::ROAD_MAINTENANCE->value,
            title: 'Arrêté exporté vers CIFS',
        );

        $rawGeoJSONRegulationOrder = new RegulationOrder(
            uuid: '06672e5f-f248-785b-8000-5f4ee64ca094',
            identifier: 'F2024/RAWGEOJSON',
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            subject: RegulationSubjectEnum::ROAD_MAINTENANCE->value,
            title: 'Arrêté avec données brutes GeoJSON',
        );

        $litteralisRegulationOrder = new RegulationOrder(
            uuid: '066e9849-f802-7a4c-8000-845f47c4b0de',
            identifier: '117374#24-A-0473',
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            title: 'Arrêté de voirie (URL : https://dl.sogelink.fr/?iX5UN3GL)',
            subject: RegulationSubjectEnum::ROAD_MAINTENANCE->value,
        );

        $winterMaintenanceRegulationOrder = new RegulationOrder(
            uuid: '804fedae-db25-4ac8-ac95-2d6cf4df7422',
            identifier: 'F2025/HIVER1',
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            title: 'Arrêté de viabilité hivernale sur la N176',
            subject: RegulationSubjectEnum::WINTER_MAINTENANCE->value,
        );
        $parkingProhibitedRegulationOrder = new RegulationOrder(
            uuid: '9d8653c5-daae-49df-8f16-6ed03cae02f4',
            identifier: '2025-01',
            category: RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            title: 'Arrêté interdiction de stationnement sur une voie',
            subject: RegulationSubjectEnum::ROAD_MAINTENANCE->value,
        );

        $manager->persist($typicalRegulationOrder);
        $manager->persist($publishedRegulationOrder);
        $manager->persist($regulationOrderPermanent);
        $manager->persist($fullCityRegulationOrder);
        $manager->persist($regulationOrderNoLocations);
        $manager->persist($regulationOrderNoMeasures);
        $manager->persist($regulationOrderCifs);
        $manager->persist($outDatedRegulationOrderCifs);
        $manager->persist($rawGeoJSONRegulationOrder);
        $manager->persist($litteralisRegulationOrder);
        $manager->persist($winterMaintenanceRegulationOrder);
        $manager->persist($parkingProhibitedRegulationOrder);
        $manager->flush();

        $this->addReference('typicalRegulationOrder', $typicalRegulationOrder);
        $this->addReference('publishedRegulationOrder', $publishedRegulationOrder);
        $this->addReference('regulationOrderPermanent', $regulationOrderPermanent);
        $this->addReference('fullCityRegulationOrder', $fullCityRegulationOrder);
        $this->addReference('regulationOrderNoLocations', $regulationOrderNoLocations);
        $this->addReference('regulationOrderNoMeasures', $regulationOrderNoMeasures);
        $this->addReference('regulationOrderCifs', $regulationOrderCifs);
        $this->addReference('outDatedRegulationOrderCifs', $outDatedRegulationOrderCifs);
        $this->addReference('rawGeoJSONRegulationOrder', $rawGeoJSONRegulationOrder);
        $this->addReference('litteralisRegulationOrder', $litteralisRegulationOrder);
        $this->addReference('winterMaintenanceRegulationOrder', $winterMaintenanceRegulationOrder);
        $this->addReference('parkingProhibitedRegulationOrder', $parkingProhibitedRegulationOrder);
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderTemplateFixture::class,
        ];
    }
}
