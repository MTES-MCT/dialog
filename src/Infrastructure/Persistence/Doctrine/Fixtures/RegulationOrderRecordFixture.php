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
        $regulationOrderRecord1 = new RegulationOrderRecord(
            '54eacea0-e1e0-4823-828d-3eae72b76da8',
            RegulationOrderRecordStatusEnum::DRAFT,
            new \DateTime('2022-01-10'),
            $this->getReference('organization1'),
        );

        $regulationOrderRecord2 = new RegulationOrderRecord(
            '2e5eb289-90c8-4c3f-8e7c-2e9e7de8948c',
            RegulationOrderRecordStatusEnum::PUBLISHED,
            new \DateTime('2022-01-10'),
            $this->getReference('organization1'),
        );

        $regulationOrderRecord3 = new RegulationOrderRecord(
            'c147cc20-ed02-4bd9-9f6b-91b67df296bd',
            RegulationOrderRecordStatusEnum::DRAFT,
            new \DateTime('2022-01-11'),
            $this->getReference('organization1'),
        );

        $regulationOrderRecord4 = new RegulationOrderRecord(
            'fd5d2e24-64e4-45c9-a8fc-097c7df796b2',
            RegulationOrderRecordStatusEnum::DRAFT,
            new \DateTime('2022-01-11'),
            $this->getReference('organization2'),
        );

        $manager->persist($regulationOrderRecord1);
        $manager->persist($regulationOrderRecord2);
        $manager->persist($regulationOrderRecord3);
        $manager->persist($regulationOrderRecord4);
        $manager->flush();

        $this->addReference('regulationOrderRecord1', $regulationOrderRecord1);
        $this->addReference('regulationOrderRecord2', $regulationOrderRecord2);
        $this->addReference('regulationOrderRecord3', $regulationOrderRecord3);
        $this->addReference('regulationOrderRecord4', $regulationOrderRecord4);
    }

    public function getDependencies(): array
    {
        return [
            OrganizationFixture::class,
        ];
    }
}
