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
        $period1 = new Period(
            uuid: '76680fcb-0d80-491b-b245-0c326eaef37b',
            measure: $this->getReference('measure3'),
            startDateTime: \DateTimeImmutable::createFromFormat('H:i', '08:00'),
            endDateTime: \DateTimeImmutable::createFromFormat('H:i', '22:00'),
            recurrenceType: 'everyDay',
        );

        $period2 = new Period(
            uuid: 'c01b254c-b7db-4761-9490-b8fea7d42312',
            measure: $this->getReference('measure1'),
            startDateTime: \DateTimeImmutable::createFromFormat('H:i', '08:00'),
            endDateTime: \DateTimeImmutable::createFromFormat('H:i', '22:00'),
            recurrenceType: 'certainDays',
        );

        $dailyRange2 = new DailyRange(
            uuid: '90e9eef7-9364-4587-9862-d8297566011f',
            applicableDays: ['monday'],
            period: $period2,
        );

        $period3 = new Period(
            uuid: '064ca7ce-ee4d-7bdb-8000-46856a6d9fa6',
            measure: $this->getReference('measure4'),
            startDateTime: \DateTimeImmutable::createFromFormat('H:i', '08:00'),
            endDateTime: \DateTimeImmutable::createFromFormat('H:i', '22:00'),
            recurrenceType: 'certainDays',
        );

        $dailyRange3 = new DailyRange(
            uuid: '2d11558e-a2d5-4f44-b688-672aa3c0e9b8',
            applicableDays: ['thursday'],
            period: $period3,
        );

        $period4 = new Period(
            uuid: '064ca7cf-43a0-7d0f-8000-b608ba0d2344',
            measure: $this->getReference('measure5'),
            startDateTime: \DateTimeImmutable::createFromFormat('H:i', '08:00'),
            endDateTime: \DateTimeImmutable::createFromFormat('H:i', '22:00'),
            recurrenceType: 'weekend',
        );

        $dailyRange4 = new DailyRange(
            uuid: 'a4a61a6c-7777-4794-8fcd-caf1effb95e4',
            applicableDays: ['tuesday', 'wednesday'],
            period: $period4,
        );

        $manager->persist($period1);
        $manager->persist($period2);
        $manager->persist($period3);
        $manager->persist($period4);
        $manager->persist($dailyRange2);
        $manager->persist($dailyRange3);
        $manager->persist($dailyRange4);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LocationFixture::class,
        ];
    }
}
