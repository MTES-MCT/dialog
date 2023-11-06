<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\RegulationOrder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class RegulationOrderFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $regulationOrder = new RegulationOrder(
            uuid: '54eacea0-e1e0-4823-828d-3eae72b76da8',
            identifier: 'FO1/2023',
            category: RegulationOrderCategoryEnum::EVENT->value,
            description: 'Description 1',
            startDate: new \DateTimeImmutable('2023-03-13'),
            endDate: new \DateTimeImmutable('2023-03-15'),
        );

        $regulationOrder2 = new RegulationOrder(
            uuid: '2e5eb289-90c8-4c3f-8e7c-2e9e7de8948c',
            identifier: 'FO2/2023',
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            description: 'Description 2',
            startDate: new \DateTimeImmutable('2023-03-10'),
            endDate: new \DateTimeImmutable('2023-03-20'),
        );

        $regulationOrder3 = new RegulationOrder(
            uuid: 'c147cc20-ed02-4bd9-9f6b-91b67df296bd',
            identifier: 'FO3/2023',
            category: RegulationOrderCategoryEnum::PERMANENT_REGULATION->value,
            description: 'Description 3',
            startDate: new \DateTimeImmutable('2023-03-11'),
            endDate: null,
        );

        $regulationOrder4 = new RegulationOrder(
            uuid: 'fd5d2e24-64e4-45c9-a8fc-097c7df796b2',
            identifier: 'FO4/2023',
            category: RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value,
            description: 'Description 4',
            startDate: null, // Simulate a regulation order before migration
            endDate: null,
        );

        $regulationOrder5 = new RegulationOrder(
            uuid: 'e589f277-ccd4-4364-967a-7e9db80e6d34',
            identifier: 'FO1/2023 (copie)',
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

        $manager->persist($regulationOrder);
        $manager->persist($regulationOrder2);
        $manager->persist($regulationOrder3);
        $manager->persist($regulationOrder4);
        $manager->persist($regulationOrder5);
        $manager->persist($regulationOrderNoMeasures);
        $manager->persist($regulationOrderCifs);
        $manager->flush();

        $this->addReference('regulationOrder', $regulationOrder);
        $this->addReference('regulationOrder2', $regulationOrder2);
        $this->addReference('regulationOrder3', $regulationOrder3);
        $this->addReference('regulationOrder4', $regulationOrder4);
        $this->addReference('regulationOrder5', $regulationOrder5);
        $this->addReference('regulationOrderNoMeasures', $regulationOrderNoMeasures);
        $this->addReference('regulationOrderCifs', $regulationOrderCifs);
    }
}
