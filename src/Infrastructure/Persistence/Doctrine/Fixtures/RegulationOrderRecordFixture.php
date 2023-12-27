<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\RegulationOrderRecord;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class RegulationOrderRecordFixture extends Fixture implements DependentFixtureInterface
{
    public const UUID_TYPICAL = 'e413a47e-5928-4353-a8b2-8b7dda27f9a5';
    public const UUID_SINGLE_LOCATION = '0658c487-1428-7a81-8000-870edc6c69d7';
    public const UUID_OTHER_ORG = '867d2be6-0d80-41b5-b1ff-8452b30a95f5';

    public function load(ObjectManager $manager): void
    {
        $typicalRegulationOrderRecord = new RegulationOrderRecord(
            self::UUID_TYPICAL,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT,
            $this->getReference('typicalRegulationOrder'),
            new \DateTime('2022-01-10'),
            $this->getReference('mainOrg'),
        );

        $regulationOrderRecord2 = new RegulationOrderRecord(
            '3ede8b1a-1816-4788-8510-e08f45511cb5',
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::PUBLISHED,
            $this->getReference('regulationOrder2'),
            new \DateTime('2022-01-10'),
            $this->getReference('mainOrg'),
        );

        $regulationOrderRecord3 = new RegulationOrderRecord(
            '4ce75a1f-82f3-40ee-8f95-48d0f04446aa',
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT,
            $this->getReference('regulationOrder3'),
            new \DateTime('2022-01-11'),
            $this->getReference('mainOrg'),
        );

        $otherOrgRegulationOrderRecord = new RegulationOrderRecord(
            self::UUID_OTHER_ORG,
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT,
            $this->getReference('otherOrgRegulationOrder'),
            new \DateTime('2022-01-11'),
            $this->getReference('otherOrg'),
        );

        $regulationOrderRecord5 = new RegulationOrderRecord(
            'b1a3e982-39a1-4f0e-8a6f-ea2fd5e872c2',
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT,
            $this->getReference('regulationOrder5'),
            new \DateTime('2022-01-10'),
            $this->getReference('mainOrg'),
        );

        $regulationOrderRecordNoMeasures = new RegulationOrderRecord(
            '0650037d-3e90-7a99-8000-a2099e71ae4a',
            RegulationOrderRecordSourceEnum::DIALOG->value,
            RegulationOrderRecordStatusEnum::DRAFT,
            $this->getReference('regulationOrderNoMeasures'),
            new \DateTime('2022-01-10'),
            $this->getReference('mainOrg'),
        );

        $manager->persist($typicalRegulationOrderRecord);
        $manager->persist($regulationOrderRecord2);
        $manager->persist($regulationOrderRecord3);
        $manager->persist($otherOrgRegulationOrderRecord);
        $manager->persist($regulationOrderRecord5);
        $manager->persist($regulationOrderRecordNoMeasures);
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
