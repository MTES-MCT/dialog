<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Condition\Period\OverallPeriod;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class OverallPeriodFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $overallPeriod = new OverallPeriod(
            'bc8739de-968a-4b76-9874-b61ca474c892',
            $this->getReference('regulationCondition'),
            new \DateTime('2022-12-08'),
            new \DateTime('2022-12-18'),
        );

        $manager->persist($overallPeriod);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationConditionFixture::class,
        ];
    }
}
