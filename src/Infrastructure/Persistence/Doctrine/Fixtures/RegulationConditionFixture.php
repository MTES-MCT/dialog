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

        $manager->persist($regulationCondition);
        $manager->flush();

        $this->addReference('regulationCondition', $regulationCondition);
    }
}
