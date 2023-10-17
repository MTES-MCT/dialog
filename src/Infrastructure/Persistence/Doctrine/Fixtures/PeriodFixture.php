<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

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
            applicableDays: ['monday', 'tuesday'],
            startTime: \DateTimeImmutable::createFromFormat('H:i', '08:00'),
            endTime: \DateTimeImmutable::createFromFormat('H:i', '22:00'),
            startDate: \DateTimeImmutable::createFromFormat('H:i', '08:00'),
            endDate: \DateTimeImmutable::createFromFormat('H:i', '22:00'),
        );

        $period2 = new Period(
            uuid: 'c01b254c-b7db-4761-9490-b8fea7d42312',
            measure: $this->getReference('measure1'),
            applicableDays: ['monday'],
            startTime: \DateTimeImmutable::createFromFormat('H:i', '08:00'),
            endTime: \DateTimeImmutable::createFromFormat('H:i', '22:00'),
            startDate: \DateTimeImmutable::createFromFormat('H:i', '08:00'),
            endDate: \DateTimeImmutable::createFromFormat('H:i', '22:00'),
        );

        $period3 = new Period(
            uuid: '064ca7ce-ee4d-7bdb-8000-46856a6d9fa6',
            measure: $this->getReference('measure4'),
            applicableDays: ['thursday'],
            startTime: \DateTimeImmutable::createFromFormat('H:i', '08:00'),
            endTime: \DateTimeImmutable::createFromFormat('H:i', '22:00'),
            startDate: \DateTimeImmutable::createFromFormat('H:i', '08:00'),
            endDate: \DateTimeImmutable::createFromFormat('H:i', '22:00'),
        );

        $period4 = new Period(
            uuid: '064ca7cf-43a0-7d0f-8000-b608ba0d2344',
            measure: $this->getReference('measure5'),
            applicableDays: ['tuesday', 'wednesday'],
            startTime: \DateTimeImmutable::createFromFormat('H:i', '08:00'),
            endTime: \DateTimeImmutable::createFromFormat('H:i', '22:00'),
            startDate: \DateTimeImmutable::createFromFormat('H:i', '08:00'),
            endDate: \DateTimeImmutable::createFromFormat('H:i', '22:00'),
        );

        $manager->persist($period1);
        $manager->persist($period2);
        $manager->persist($period3);
        $manager->persist($period4);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LocationFixture::class,
        ];
    }
}
