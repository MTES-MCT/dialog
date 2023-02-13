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

        $regulationCondition3 = new RegulationCondition(
            'f171375d-343e-4373-8848-39d4370d92f8',
            false,
        );

        $regulationCondition4 = new RegulationCondition(
            '4e43c2d3-788c-404e-b741-e3a501d0ce9f',
            false,
        );

        $manager->persist($regulationCondition);
        $manager->persist($regulationCondition2);
        $manager->persist($regulationCondition3);
        $manager->persist($regulationCondition4);
        $manager->flush();

        $this->addReference('regulationCondition1', $regulationCondition);
        $this->addReference('regulationCondition2', $regulationCondition2);
        $this->addReference('regulationCondition3', $regulationCondition3);
        $this->addReference('regulationCondition4', $regulationCondition4);
    }
}
