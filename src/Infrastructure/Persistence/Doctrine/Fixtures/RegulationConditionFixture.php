<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Condition\RegulationCondition;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class RegulationConditionFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $regulationCondition = new RegulationCondition(
            'a7920ccc-ceef-40ef-b62c-8a925ff5e43b',
            false,
        );

        $regulationCondition2 = new RegulationCondition(
            '945332d9-d649-44bd-b530-fb574fe849da',
            false,
        );

        $manager->persist($regulationCondition);
        $manager->persist($regulationCondition2);
        $manager->flush();

        $this->addReference('regulationCondition1', $regulationCondition);
        $this->addReference('regulationCondition2', $regulationCondition2);
    }
}
