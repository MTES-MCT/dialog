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
            $this->getReference('regulationOrder'),
            new \DateTime('2022-01-10'),
            $this->getReference('organization1'),
        );

        $regulationOrderRecord2 = new RegulationOrderRecord(
            '3ede8b1a-1816-4788-8510-e08f45511cb5',
            RegulationOrderRecordStatusEnum::PUBLISHED,
            $this->getReference('regulationOrder2'),
            new \DateTime('2022-01-10'),
            $this->getReference('organization1'),
        );

        $regulationOrderRecord3 = new RegulationOrderRecord(
            '4ce75a1f-82f3-40ee-8f95-48d0f04446aa',
            RegulationOrderRecordStatusEnum::DRAFT,
            $this->getReference('regulationOrder3'),
            new \DateTime('2022-01-11'),
            $this->getReference('organization1'),
        );

        $regulationOrderRecord4 = new RegulationOrderRecord(
            '867d2be6-0d80-41b5-b1ff-8452b30a95f5',
            RegulationOrderRecordStatusEnum::DRAFT,
            $this->getReference('regulationOrder4'),
            new \DateTime('2022-01-11'),
            $this->getReference('organization2'),
        );

        $manager->persist($regulationOrderRecord);
        $manager->persist($regulationOrderRecord2);
        $manager->persist($regulationOrderRecord3);
        $manager->persist($regulationOrderRecord4);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RegulationOrderFixture::class,
            OrganizationFixture::class,
        ];
    }
}
