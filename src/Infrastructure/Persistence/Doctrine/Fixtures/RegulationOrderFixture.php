<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\RegulationOrder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class RegulationOrderFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $regulationOrder = new RegulationOrder(
            '54eacea0-e1e0-4823-828d-3eae72b76da8',
            'Description 1',
            'Autorité 1',
            $this->getReference('regulationCondition1'),
        );

        $regulationOrder2 = new RegulationOrder(
            '2e5eb289-90c8-4c3f-8e7c-2e9e7de8948c',
            'Description 2',
            'Autorité 2',
            $this->getReference('regulationCondition2'),
        );

        $regulationOrder3 = new RegulationOrder(
            'c147cc20-ed02-4bd9-9f6b-91b67df296bd',
            'Description 3',
            'Autorité 3',
            $this->getReference('regulationCondition3'),
        );

        $manager->persist($regulationOrder);
        $manager->persist($regulationOrder2);
        $manager->persist($regulationOrder3);
        $manager->flush();

        $this->addReference('regulationOrder', $regulationOrder);
        $this->addReference('regulationOrder2', $regulationOrder2);
        $this->addReference('regulationOrder3', $regulationOrder3);
    }

    public function getDependencies(): array
    {
        return [
            RegulationConditionFixture::class,
        ];
    }
}
