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
    public const NUM_TEMPORARY = 7;
    public const NUM_PERMANENT = 1;

    public function load(ObjectManager $manager): void
    {
        $typicalRegulationOrder = new RegulationOrder(
            uuid: '54eacea0-e1e0-4823-828d-3eae72b76da8',
            identifier: self::TYPICAL_IDENTIFIER,
            category: RegulationOrderCategoryEnum::EVENT->value,
            description: 'Description 1',
            startDate: new \DateTimeImmutable('2023-03-13'),
            endDate: new \DateTimeImmutable('2023-03-15'),
        );

        $publishedRegulationOrder = new RegulationOrder(
            uuid: '2e5eb289-90c8-4c3f-8e7c-2e9e7de8948c',
            identifier: 'FO2/2023',
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            description: 'Description 2',
            startDate: new \DateTimeImmutable('2023-03-10'),
            endDate: new \DateTimeImmutable('2023-03-20'),
        );

        $regulationOrderDuplicate = new RegulationOrder(
            uuid: '0658c6ab-6b49-7a3b-8000-0683622905a3',
            identifier: 'FO2/2023 (copie)',
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            description: 'Description 2',
            startDate: new \DateTimeImmutable('2023-03-10'),
            endDate: new \DateTimeImmutable('2023-03-20'),
        );

        $regulationOrderPermanent = new RegulationOrder(
            uuid: 'c147cc20-ed02-4bd9-9f6b-91b67df296bd',
            identifier: 'FO3/2023',
            category: RegulationOrderCategoryEnum::PERMANENT_REGULATION->value,
            description: 'Description 3',
            startDate: new \DateTimeImmutable('2023-03-11'),
            endDate: null,
        );

        $otherOrgRegulationOrder = new RegulationOrder(
            uuid: 'fd5d2e24-64e4-45c9-a8fc-097c7df796b2',
            identifier: 'FO4/2023',
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            description: 'Description 4',
            startDate: null, // Simulate a regulation order before migration
            endDate: null,
        );

        $fullCityRegulationOrder = new RegulationOrder(
            uuid: '0658c568-dfbe-7c64-8000-303f7e2ae9b3',
            identifier: 'F2023/full-city',
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            description: 'Description 2',
            startDate: new \DateTimeImmutable('2023-03-11'),
            endDate: new \DateTimeImmutable('2023-03-21'),
        );

        $regulationOrderNoLocations = new RegulationOrder(
            uuid: 'e589f277-ccd4-4364-967a-7e9db80e6d34',
            identifier: 'F2023/no-locations',
            description: 'Description 5 that is very long and will be truncated',
            category: RegulationOrderCategoryEnum::OTHER->value,
            otherCategoryText: 'Dérogation préfectorale',
            startDate: new \DateTimeImmutable('2023-03-13'),
            endDate: new \DateTimeImmutable('2023-03-15'),
        );

        $regulationOrderNoMeasures = new RegulationOrder(
            uuid: '0650037e-8f8e-7f66-8000-c8ebe51493b9',
            identifier: 'FO14/2023',
            description: 'No measures',
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            startDate: new \DateTimeImmutable('2023-01-13'),
            endDate: new \DateTimeImmutable('2023-01-15'),
        );

        $regulationOrderCifs = new RegulationOrder(
            uuid: '06549047-db9d-74bb-8000-754a6f2ff4c3',
            identifier: 'F/CIFS/2021',
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            description: 'Arrêté exporté vers CIFS',
            startDate: new \DateTimeImmutable('2021-11-02'),
            endDate: new \DateTimeImmutable('2021-11-06'),
        );

        $manager->persist($typicalRegulationOrder);
        $manager->persist($publishedRegulationOrder);
        $manager->persist($regulationOrderDuplicate);
        $manager->persist($regulationOrderPermanent);
        $manager->persist($otherOrgRegulationOrder);
        $manager->persist($fullCityRegulationOrder);
        $manager->persist($regulationOrderNoLocations);
        $manager->persist($regulationOrderNoMeasures);
        $manager->persist($regulationOrderCifs);
        $manager->flush();

        $this->addReference('typicalRegulationOrder', $typicalRegulationOrder);
        $this->addReference('publishedRegulationOrder', $publishedRegulationOrder);
        $this->addReference('regulationOrderPermanent', $regulationOrderPermanent);
        $this->addReference('otherOrgRegulationOrder', $otherOrgRegulationOrder);
        $this->addReference('fullCityRegulationOrder', $fullCityRegulationOrder);
        $this->addReference('regulationOrderNoLocations', $regulationOrderNoLocations);
        $this->addReference('regulationOrderNoMeasures', $regulationOrderNoMeasures);
        $this->addReference('regulationOrderDuplicate', $regulationOrderDuplicate);
        $this->addReference('regulationOrderCifs', $regulationOrderCifs);
    }
}
