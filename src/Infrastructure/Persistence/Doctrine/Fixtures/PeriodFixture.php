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
        // NOTE : les dates sont à saisir "prêtes à enregistrer en DB", donc faire attention à la timezone
        $tz = new \DateTimeZone('Etc/GMT-1');

        $typicalPeriod = new Period(
            uuid: '76680fcb-0d80-491b-b245-0c326eaef37b',
            measure: $this->getReference('typicalMeasure'),
            startDateTime: new \DateTimeImmutable('2023-10-31 08:00:00'),
            endDateTime: new \DateTimeImmutable('2023-10-31 22:00:00'),
            recurrenceType: 'everyDay',
        );
        $publishedPeriod = new Period(
            uuid: '067164f2-cbd3-7fc6-8000-7ce25ec9c17b',
            measure: $this->getReference('publishedMeasure'),
            startDateTime: new \DateTimeImmutable('2023-03-10 00:00:00', $tz),
            endDateTime: new \DateTimeImmutable('2023-03-20 23:59:00', $tz),
            recurrenceType: 'everyDay',
        );
        $permanentPeriod = new Period(
            uuid: '06717be6-ddb3-7635-8000-431d53fcd535',
            measure: $this->getReference('permanentMeasure'),
            startDateTime: new \DateTimeImmutable('2023-03-11 00:00:00', $tz),
            endDateTime: null,
            recurrenceType: 'everyDay',
        );
        $fullCityPeriod = new Period(
            uuid: '06718fa9-91b2-74a2-8000-4a5d3c841d67',
            measure: $this->getReference('fullCityMeasure'),
            startDateTime: new \DateTimeImmutable('2023-03-11 00:00:00', $tz),
            endDateTime: new \DateTimeImmutable('2023-03-21 23:59:00', $tz),
            recurrenceType: 'everyDay',
        );
        $outDatedCifsPeriod = new Period(
            uuid: 'e1591887-1de6-4362-a5d7-3f2553cb49dd',
            measure: $this->getReference('outDatedCifsMeasure'),
            startDateTime: new \DateTimeImmutable('2021-11-02 00:00:00', $tz),
            endDateTime: new \DateTimeImmutable('2021-11-06 23:59:00', $tz),
            recurrenceType: PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value,
        );
        $cifsPeriod1 = new Period(
            uuid: '06548fe3-7bfb-73af-8000-f7f34af31312',
            measure: $this->getReference('cifsMeasure'),
            startDateTime: new \DateTimeImmutable('2023-06-05 00:00:00', $tz),
            endDateTime: new \DateTimeImmutable('2023-06-10 23:59:00', $tz),
            recurrenceType: PeriodRecurrenceTypeEnum::CERTAIN_DAYS->value,
        );

        $cifsDailyRange1 = new DailyRange(
            uuid: '0654b638-800c-73a8-8000-3edefca88689',
            applicableDays: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            period: $cifsPeriod1,
        );
        $outDatedCifsDailyRange = new DailyRange(
            uuid: '6f11c0cb-d2a2-4429-b914-9bfdf486a051',
            applicableDays: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            period: $outDatedCifsPeriod,
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
            startDateTime: new \DateTimeImmutable('2023-06-02 00:00:00', $tz),
            endDateTime: new \DateTimeImmutable('2023-06-06 23:59:00', $tz),
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
            startDateTime: new \DateTimeImmutable('2023-06-03 08:00:00'),
            endDateTime: new \DateTimeImmutable('2023-06-05 10:00:00'),
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

        $rawGeoJSONPeriod = new Period(
            uuid: '06718fb1-ef69-7120-8000-27d815e502fd',
            measure: $this->getReference('rawGeoJSONMeasure'),
            startDateTime: new \DateTimeImmutable('2020-06-02 00:00:00', $tz),
            endDateTime: new \DateTimeImmutable('2020-06-10 23:59:00', $tz),
            recurrenceType: 'everyDay',
        );

        $litteralisPeriod = new Period(
            uuid: '0671628e-04f8-7c89-8000-43928d1376ba',
            measure: $this->getReference('litteralisMeasure'),
            startDateTime: new \DateTimeImmutable('2023-07-03 00:00:00', $tz),
            endDateTime: new \DateTimeImmutable('2023-11-10 23:59:00', $tz),
            recurrenceType: PeriodRecurrenceTypeEnum::EVERY_DAY->value,
        );

        $manager->persist($typicalPeriod);
        $manager->persist($publishedPeriod);
        $manager->persist($permanentPeriod);
        $manager->persist($fullCityPeriod);
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
        $manager->persist($outDatedCifsPeriod);
        $manager->persist($outDatedCifsDailyRange);
        $manager->persist($rawGeoJSONPeriod);
        $manager->persist($litteralisPeriod);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MeasureFixture::class,
        ];
    }
}
