<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\RegulationOrder\RegulationOrder;
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
            $this->getReference('regulationCondition'),
        );

        $manager->persist($regulationOrder);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationConditionFixture::class,
        ];
    }
}
