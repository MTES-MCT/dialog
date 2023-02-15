<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\RegulationOrder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class RegulationOrderFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $regulationOrder = new RegulationOrder(
            uuid: '54eacea0-e1e0-4823-828d-3eae72b76da8',
            issuingAuthority: 'Autorité 1',
            description: 'Description 1',
        );

        $regulationOrder2 = new RegulationOrder(
            uuid: '2e5eb289-90c8-4c3f-8e7c-2e9e7de8948c',
            issuingAuthority: 'Autorité 2',
            description: 'Description 2',
        );

        $regulationOrder3 = new RegulationOrder(
            uuid: 'c147cc20-ed02-4bd9-9f6b-91b67df296bd',
            issuingAuthority: 'Description 3',
            description: 'Autorité 3',
        );

        $regulationOrder4 = new RegulationOrder(
            uuid: 'fd5d2e24-64e4-45c9-a8fc-097c7df796b2',
            issuingAuthority: 'Description 4',
            description: 'Autorité 4',
        );

        $manager->persist($regulationOrder);
        $manager->persist($regulationOrder2);
        $manager->persist($regulationOrder3);
        $manager->persist($regulationOrder4);
        $manager->flush();

        $this->addReference('regulationOrder', $regulationOrder);
        $this->addReference('regulationOrder2', $regulationOrder2);
        $this->addReference('regulationOrder3', $regulationOrder3);
        $this->addReference('regulationOrder4', $regulationOrder4);
    }
}
