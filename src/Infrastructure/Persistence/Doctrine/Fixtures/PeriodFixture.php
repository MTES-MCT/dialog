<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Period;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class PeriodFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $typicalPeriod = new Period(
            uuid: '76680fcb-0d80-491b-b245-0c326eaef37b',
            measure: $this->getReference('typicalMeasure'),
            startDateTime: new \DateTimeImmutable('2023-10-31 08:00:00'),
            endDateTime: new \DateTimeImmutable('2023-10-31 22:00:00'),
            recurrenceType: 'everyDay',
        );

        $permanentMeasurePeriod = new Period(
            uuid: 'c01b254c-b7db-4761-9490-b8fea7d42312',
            measure: $this->getReference('permanentRegulationOrderLocationMeasure1'),
            startDateTime: new \DateTimeImmutable('2023-10-31 08:00:00'),
            endDateTime: new \DateTimeImmutable('2023-10-31 22:00:00'),
            recurrenceType: 'certainDays',
        );

        $permanentMeasureDailyRange = new DailyRange(
            uuid: '90e9eef7-9364-4587-9862-d8297566011f',
            applicableDays: ['monday'],
            period: $permanentMeasurePeriod,
        );

        $publishedLocation1Measure1Period = new Period(
            uuid: '064ca7ce-ee4d-7bdb-8000-46856a6d9fa6',
            measure: $this->getReference('publishedLocation1Measure1'),
            startDateTime: new \DateTimeImmutable('2023-10-31 08:00:00'),
            endDateTime: new \DateTimeImmutable('2023-10-31 22:00:00'),
            recurrenceType: 'certainDays',
        );

        $publishedLocation1Measure1DailyRange = new DailyRange(
            uuid: '2d11558e-a2d5-4f44-b688-672aa3c0e9b8',
            applicableDays: ['monday', 'thursday'],
            period: $publishedLocation1Measure1Period,
        );

        $publishedLocation1Measure2Period = new Period(
            uuid: '064ca7cf-43a0-7d0f-8000-b608ba0d2344',
            measure: $this->getReference('publishedLocation1Measure2'),
            startDateTime: new \DateTimeImmutable('2023-10-31 08:00:00'),
            endDateTime: new \DateTimeImmutable('2023-10-31 22:00:00'),
            recurrenceType: 'weekend',
        );

        $publishedLocation1Measure2DailyRange = new DailyRange(
            uuid: 'a4a61a6c-7777-4794-8fcd-caf1effb95e4',
            applicableDays: ['tuesday', 'wednesday'],
            period: $publishedLocation1Measure2Period,
        );

        $manager->persist($typicalPeriod);
        $manager->persist($permanentMeasurePeriod);
        $manager->persist($permanentMeasureDailyRange);
        $manager->persist($publishedLocation1Measure1Period);
        $manager->persist($publishedLocation1Measure1DailyRange);
        $manager->persist($publishedLocation1Measure2Period);
        $manager->persist($publishedLocation1Measure2DailyRange);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LocationFixture::class,
        ];
    }
}
