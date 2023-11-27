<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;
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
            startDateTime: new \DateTimeImmutable('2023-03-13 00:00:00'),
            endDateTime: new \DateTimeImmutable('2023-03-15 23:59:00'),
            recurrenceType: 'everyDay',
        );

        $period2 = new Period(
            uuid: 'c01b254c-b7db-4761-9490-b8fea7d42312',
            measure: $this->getReference('measure1'),
            startDateTime: new \DateTimeImmutable('2023-03-11 00:00:00'),
            endDateTime: new \DateTimeImmutable('2023-04-01 23:59:00'),
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
            startDateTime: new \DateTimeImmutable('2023-03-10 08:00:00'),
            endDateTime: new \DateTimeImmutable('2023-03-20 22:00:00'),
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
            startDateTime: new \DateTimeImmutable('2023-03-10 08:00:00'),
            endDateTime: new \DateTimeImmutable('2023-03-20 22:00:00'),
            recurrenceType: 'weekend',
        );

        $dailyRange4 = new DailyRange(
            uuid: 'a4a61a6c-7777-4794-8fcd-caf1effb95e4',
            applicableDays: ['tuesday', 'wednesday'],
            period: $period4,
        );

        $periodCifs1 = new Period(
            uuid: '06548fe3-7bfb-73af-8000-f7f34af31312',
            measure: $this->getReference('measureCifs'),
            startDateTime: new \DateTimeImmutable('2021-11-02'),
            endDateTime: new \DateTimeImmutable('2021-11-06'),
            recurrenceType: PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value,
        );
        $dailyRangeCifs1 = new DailyRange(
            uuid: '0654b638-800c-73a8-8000-3edefca88689',
            applicableDays: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            period: $periodCifs1,
        );
        $timeSlotCifs1 = new TimeSlot(
            uuid: '0654b639-6bea-7657-8000-1b27c6339db4',
            period: $periodCifs1,
            startTime: \DateTimeImmutable::createFromFormat('H:i', '18:00'),
            endTime: \DateTimeImmutable::createFromFormat('H:i', '22:00'),
        );

        $periodCifs2 = new Period(
            uuid: '0654b639-cd33-7507-8000-e2ea21673135',
            measure: $this->getReference('measureCifs'),
            startDateTime: new \DateTimeImmutable('2021-11-02'),
            endDateTime: new \DateTimeImmutable('2021-11-06'),
            recurrenceType: PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value,
        );
        $dailyRangeCifs2 = new DailyRange(
            uuid: '0654b63a-1061-7004-8000-6fe6f1f455f5',
            applicableDays: ['tuesday'],
            period: $periodCifs2,
        );
        $timeSlot1DailyRangeCifs2 = new TimeSlot(
            uuid: '0654b63a-49fd-76fd-8000-6befe7bc91bb',
            period: $periodCifs2,
            startTime: \DateTimeImmutable::createFromFormat('H:i', '12:00'),
            endTime: \DateTimeImmutable::createFromFormat('H:i', '14:00'),
        );
        $timeSlot2DailyRangeCifs2 = new TimeSlot(
            uuid: '0654bb14-049c-7516-8000-916303927b43',
            period: $periodCifs2,
            startTime: \DateTimeImmutable::createFromFormat('H:i', '19:00'),
            endTime: \DateTimeImmutable::createFromFormat('H:i', '21:00'),
        );

        $periodCifs3 = new Period(
            uuid: '0654b63a-838d-798b-8000-044b619f225d',
            measure: $this->getReference('measureCifs'),
            startDateTime: new \DateTimeImmutable('2021-11-02'),
            endDateTime: new \DateTimeImmutable('2021-11-06'),
            recurrenceType: PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value,
        );
        $dailyRangeCifs3 = new DailyRange(
            uuid: '0654b63a-c902-773b-8000-9914565f2d96',
            applicableDays: ['tuesday', 'thursday'],
            period: $periodCifs3,
        );
        $timeSlotCifs3 = new TimeSlot(
            uuid: '0654b63b-0890-7177-8000-8ef011ad20ba',
            period: $periodCifs3,
            startTime: \DateTimeImmutable::createFromFormat('H:i', '08:00'),
            endTime: \DateTimeImmutable::createFromFormat('H:i', '10:00'),
        );

        $manager->persist($period1);
        $manager->persist($period2);
        $manager->persist($period3);
        $manager->persist($period4);
        $manager->persist($periodCifs1);
        $manager->persist($periodCifs2);
        $manager->persist($periodCifs3);
        $manager->persist($dailyRange2);
        $manager->persist($dailyRange3);
        $manager->persist($dailyRange4);
        $manager->persist($dailyRangeCifs1);
        $manager->persist($dailyRangeCifs2);
        $manager->persist($dailyRangeCifs3);
        $manager->persist($timeSlotCifs1);
        $manager->persist($timeSlot1DailyRangeCifs2);
        $manager->persist($timeSlot2DailyRangeCifs2);
        $manager->persist($timeSlotCifs3);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LocationFixture::class,
        ];
    }
}
