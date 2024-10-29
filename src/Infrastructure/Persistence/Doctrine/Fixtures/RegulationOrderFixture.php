<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\RegulationOrder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class RegulationOrderFixture extends Fixture
{
    public const TYPICAL_IDENTIFIER = 'FO1/2023';
    public const IDENTIFIER_CIFS = 'F/CIFS/2023';

    public function load(ObjectManager $manager): void
    {
        // NOTE : les dates sont à saisir "prêtes à enregistrer en DB", donc faire attention à la timezone
        $tz = new \DateTimeZone('Etc/GMT-1');

        $typicalRegulationOrder = new RegulationOrder(
            uuid: '54eacea0-e1e0-4823-828d-3eae72b76da8',
            identifier: self::TYPICAL_IDENTIFIER,
            category: RegulationOrderCategoryEnum::EVENT->value,
            description: 'Description 1',
            startDate: new \DateTimeImmutable('2023-03-13', $tz),
            endDate: new \DateTimeImmutable('2023-03-15', $tz),
        );

        $publishedRegulationOrder = new RegulationOrder(
            uuid: '2e5eb289-90c8-4c3f-8e7c-2e9e7de8948c',
            identifier: 'FO2/2023',
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            description: 'Description 2',
            startDate: new \DateTimeImmutable('2023-03-10', $tz),
            endDate: new \DateTimeImmutable('2023-03-20', $tz),
        );

        $regulationOrderPermanent = new RegulationOrder(
            uuid: 'c147cc20-ed02-4bd9-9f6b-91b67df296bd',
            identifier: 'FO3/2023',
            category: RegulationOrderCategoryEnum::PERMANENT_REGULATION->value,
            description: 'Description 3',
            startDate: new \DateTimeImmutable('2023-03-11', $tz),
            endDate: null,
        );

        $fullCityRegulationOrder = new RegulationOrder(
            uuid: '0658c568-dfbe-7c64-8000-303f7e2ae9b3',
            identifier: 'F2023/full-city',
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            description: 'Description 2',
            startDate: new \DateTimeImmutable('2023-03-11', $tz),
            endDate: new \DateTimeImmutable('2023-03-21', $tz),
        );

        $regulationOrderNoLocations = new RegulationOrder(
            uuid: 'e589f277-ccd4-4364-967a-7e9db80e6d34',
            identifier: 'F2023/no-locations',
            description: 'Description 5 that is very long and will be truncated',
            category: RegulationOrderCategoryEnum::OTHER->value,
            otherCategoryText: 'Dérogation préfectorale',
            startDate: new \DateTimeImmutable('2023-07-13 00:00:00', $tz),
            endDate: new \DateTimeImmutable('2023-07-15 00:00:00', $tz),
        );

        $regulationOrderNoMeasures = new RegulationOrder(
            uuid: '0650037e-8f8e-7f66-8000-c8ebe51493b9',
            identifier: 'FO14/2023',
            description: 'No measures',
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            startDate: new \DateTimeImmutable('2023-01-13', $tz),
            endDate: new \DateTimeImmutable('2023-01-15', $tz),
        );

        $regulationOrderCifs = new RegulationOrder(
            uuid: '06549047-db9d-74bb-8000-754a6f2ff4c3',
            identifier: self::IDENTIFIER_CIFS,
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            description: 'Arrêté exporté vers CIFS',
            startDate: new \DateTimeImmutable('2023-06-02', $tz),
            endDate: new \DateTimeImmutable('2023-06-10', $tz),
        );

        $outDatedRegulationOrderCifs = new RegulationOrder(
            uuid: 'edc8dd18-5f56-4684-b2ba-d18658d53518',
            identifier: 'F/OUTDATED/CIFS/2021',
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            description: 'Arrêté exporté vers CIFS',
            startDate: new \DateTimeImmutable('2021-06-02', $tz),
            endDate: new \DateTimeImmutable('2021-06-10', $tz),
        );

        $rawGeoJSONRegulationOrder = new RegulationOrder(
            uuid: '06672e5f-f248-785b-8000-5f4ee64ca094',
            identifier: 'F2024/RAWGEOJSON',
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            description: 'Arrêté avec données brutes GeoJSON',
            startDate: new \DateTimeImmutable('2020-06-02', $tz),
            endDate: new \DateTimeImmutable('2020-06-10', $tz),
        );

        $litteralisRegulationOrder = new RegulationOrder(
            uuid: '066e9849-f802-7a4c-8000-845f47c4b0de',
            identifier: '117374#24-A-0473',
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            description: 'Arrêté de voirie (URL : https://dl.sogelink.fr/?iX5UN3GL)',
            startDate: new \DateTimeImmutable('2023-06-03', $tz),
            endDate: new \DateTimeImmutable('2023-11-10', $tz),
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
    }
}
