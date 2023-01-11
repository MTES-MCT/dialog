<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrderRecord;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class RegulationOrderRecordFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $regulationOrderRecord = new RegulationOrderRecord(
            'e413a47e-5928-4353-a8b2-8b7dda27f9a5',
            RegulationOrderRecordStatusEnum::DRAFT,
            1,
            $this->getReference('regulationOrder'),
            new \DateTime('2022-01-10'),
        );

        $regulationOrderRecord2 = new RegulationOrderRecord(
            '3ede8b1a-1816-4788-8510-e08f45511cb5',
            RegulationOrderRecordStatusEnum::PUBLISHED,
            5,
            $this->getReference('regulationOrder2'),
            new \DateTime('2022-01-10'),
        );

        $regulationOrderRecord3 = new RegulationOrderRecord(
            '4ce75a1f-82f3-40ee-8f95-48d0f04446aa',
            RegulationOrderRecordStatusEnum::DRAFT,
            5,
            $this->getReference('regulationOrder3'),
            new \DateTime('2022-01-11'),
        );

        $manager->persist($regulationOrderRecord);
        $manager->persist($regulationOrderRecord2);
        $manager->persist($regulationOrderRecord3);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderFixture::class,
        ];
    }
}
