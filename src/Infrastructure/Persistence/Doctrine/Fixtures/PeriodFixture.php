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
        $typicalPeriod = new Period(
            uuid: '76680fcb-0d80-491b-b245-0c326eaef37b',
            measure: $this->getReference('typicalMeasure'),
            startDateTime: new \DateTimeImmutable('2023-10-31 08:00:00'),
            endDateTime: new \DateTimeImmutable('2023-10-31 22:00:00'),
            recurrenceType: 'everyDay',
        );
        $cifsPeriod1 = new Period(
            uuid: '06548fe3-7bfb-73af-8000-f7f34af31312',
            measure: $this->getReference('cifsMeasure'),
            startDateTime: new \DateTimeImmutable('2021-11-02'),
            endDateTime: new \DateTimeImmutable('2021-11-06'),
            recurrenceType: PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value,
        );

        $cifsDailyRange1 = new DailyRange(
            uuid: '0654b638-800c-73a8-8000-3edefca88689',
            applicableDays: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            period: $cifsPeriod1,
        );
        $cifsTimeSlot1 = new TimeSlot(
            uuid: '0654b639-6bea-7657-8000-1b27c6339db4',
            period: $cifsPeriod1,
            startTime: \DateTimeImmutable::createFromFormat('H:i', '18:00'),
            endTime: \DateTimeImmutable::createFromFormat('H:i', '22:00'),
        );

        $cifsPeriod2 = new Period(
            uuid: '0654b639-cd33-7507-8000-e2ea21673135',
            measure: $this->getReference('cifsMeasure'),
            startDateTime: new \DateTimeImmutable('2021-11-02'),
            endDateTime: new \DateTimeImmutable('2021-11-06'),
            recurrenceType: PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value,
        );
        $cifsDailyRange2 = new DailyRange(
            uuid: '0654b63a-1061-7004-8000-6fe6f1f455f5',
            applicableDays: ['tuesday'],
            period: $cifsPeriod2,
        );
        $cifsDailyRange2TimeSlot1 = new TimeSlot(
            uuid: '0654b63a-49fd-76fd-8000-6befe7bc91bb',
            period: $cifsPeriod2,
            startTime: \DateTimeImmutable::createFromFormat('H:i', '12:00'),
            endTime: \DateTimeImmutable::createFromFormat('H:i', '14:00'),
        );
        $cifsDailyRange2TimeSlot2 = new TimeSlot(
            uuid: '0654bb14-049c-7516-8000-916303927b43',
            period: $cifsPeriod2,
            startTime: \DateTimeImmutable::createFromFormat('H:i', '19:00'),
            endTime: \DateTimeImmutable::createFromFormat('H:i', '21:00'),
        );

        $cifsPeriod3 = new Period(
            uuid: '0654b63a-838d-798b-8000-044b619f225d',
            measure: $this->getReference('cifsMeasure'),
            startDateTime: new \DateTimeImmutable('2021-11-02'),
            endDateTime: new \DateTimeImmutable('2021-11-06'),
            recurrenceType: PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value,
        );
        $cifsDailyRange3 = new DailyRange(
            uuid: '0654b63a-c902-773b-8000-9914565f2d96',
            applicableDays: ['tuesday', 'thursday'],
            period: $cifsPeriod3,
        );
        $cifsTimeSlot3 = new TimeSlot(
            uuid: '0654b63b-0890-7177-8000-8ef011ad20ba',
            period: $cifsPeriod3,
            startTime: \DateTimeImmutable::createFromFormat('H:i', '08:00'),
            endTime: \DateTimeImmutable::createFromFormat('H:i', '10:00'),
        );

        $manager->persist($typicalPeriod);
        $manager->persist($cifsPeriod1);
        $manager->persist($cifsDailyRange1);
        $manager->persist($cifsTimeSlot1);
        $manager->persist($cifsPeriod2);
        $manager->persist($cifsDailyRange2);
        $manager->persist($cifsDailyRange2TimeSlot1);
        $manager->persist($cifsDailyRange2TimeSlot2);
        $manager->persist($cifsPeriod3);
        $manager->persist($cifsDailyRange3);
        $manager->persist($cifsTimeSlot3);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MeasureFixture::class,
        ];
    }
}
