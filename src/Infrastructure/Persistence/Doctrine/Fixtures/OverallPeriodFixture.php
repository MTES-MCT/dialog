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
            $this->getReference('regulationCondition1'),
            startDate: new \DateTime('2022-12-08'),
            startTime: new \DateTimeImmutable('08:00:00'), // UTC
            endDate: new \DateTime('2022-12-18'),
            endTime: new \DateTimeImmutable('16:00:00'), // UTC
        );

        $overallPeriod2 = new OverallPeriod(
            '66a52b5d-71a8-4a09-abda-fca30ddce7b1',
            $this->getReference('regulationCondition2'),
            startDate: new \DateTime('2022-10-08'),
        );

        $manager->persist($overallPeriod);
        $manager->persist($overallPeriod2);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationConditionFixture::class,
        ];
    }
}
